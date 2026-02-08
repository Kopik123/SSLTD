package com.ssltd.fieldapp.work

import android.content.Context
import androidx.work.Constraints
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.workDataOf

object UploadWork {
  private const val KEY_UPLOAD_ID = "upload_id"

  fun enqueue(context: Context, uploadId: Long) {
    val constraints = Constraints.Builder()
      .setRequiredNetworkType(NetworkType.CONNECTED)
      .build()

    val req = OneTimeWorkRequestBuilder<UploadWorker>()
      .setConstraints(constraints)
      .setInputData(workDataOf(KEY_UPLOAD_ID to uploadId))
      .build()

    WorkManager.getInstance(context.applicationContext)
      .enqueueUniqueWork("upload_$uploadId", ExistingWorkPolicy.REPLACE, req)
  }

  fun inputUploadId(dataKey: String = KEY_UPLOAD_ID): String = dataKey
}

