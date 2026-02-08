package com.ssltd.fieldapp.work

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.ssltd.fieldapp.data.AuthStore
import com.ssltd.fieldapp.data.api.ApiClient
import com.ssltd.fieldapp.data.db.AppDb
import com.ssltd.fieldapp.data.db.UploadStatus
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.HttpException
import java.io.File

class UploadWorker(
  appContext: Context,
  params: WorkerParameters,
) : CoroutineWorker(appContext, params) {

  override suspend fun doWork(): Result {
    val uploadId = inputData.getLong(UploadWork.inputUploadId(), 0L)
    if (uploadId <= 0L) return Result.success()

    val db = AppDb.get(applicationContext)
    val dao = db.queuedUploadDao()
    val item = dao.getById(uploadId) ?: return Result.success()

    val authStore = AuthStore(applicationContext)
    val token = authStore.token()
    if (token.isNullOrBlank()) {
      dao.updateState(item.id, UploadStatus.FAILED, item.attempts, "not_authenticated", System.currentTimeMillis())
      return Result.success()
    }

    val file = File(item.filePath)
    if (!file.exists() || !file.isFile) {
      dao.updateState(item.id, UploadStatus.FAILED, item.attempts, "file_missing", System.currentTimeMillis())
      return Result.success()
    }

    val attempts = item.attempts + 1
    dao.updateState(item.id, UploadStatus.UPLOADING, attempts, null, System.currentTimeMillis())

    try {
      val api = ApiClient.create(authStore)

      val media = item.mimeType.toMediaTypeOrNull() ?: "application/octet-stream".toMediaType()
      val body = file.asRequestBody(media)
      val part = MultipartBody.Part.createFormData("file", item.originalName, body)

      api.upload(
        ownerType = item.ownerType.toRequestBody("text/plain".toMediaType()),
        ownerId = item.ownerId.toString().toRequestBody("text/plain".toMediaType()),
        stage = item.stage.toRequestBody("text/plain".toMediaType()),
        clientVisible = "0".toRequestBody("text/plain".toMediaType()),
        file = part,
      )

      // Success: remove from queue and delete local pending file.
      dao.deleteById(item.id)
      try { file.delete() } catch (_: Throwable) { /* ignore */ }
      return Result.success()
    } catch (t: Throwable) {
      val now = System.currentTimeMillis()
      val msg = when (t) {
        is HttpException -> "http_${t.code()}"
        else -> (t.message ?: "upload_failed")
      }

      // Cap retries to avoid endless background loops.
      if (attempts >= 10) {
        dao.updateState(item.id, UploadStatus.FAILED, attempts, msg, now)
        return Result.success()
      }

      dao.updateState(item.id, UploadStatus.PENDING, attempts, msg, now)
      return Result.retry()
    }
  }
}

