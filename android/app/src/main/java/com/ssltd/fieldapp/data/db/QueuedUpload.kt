package com.ssltd.fieldapp.data.db

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
  tableName = "queued_uploads",
  indices = [
    Index(value = ["status"]),
    Index(value = ["ownerType", "ownerId"]),
    Index(value = ["createdAtMs"]),
  ],
)
data class QueuedUpload(
  @PrimaryKey(autoGenerate = true)
  val id: Long = 0,

  val ownerType: String,
  val ownerId: Int,
  val stage: String,

  val filePath: String,
  val originalName: String,
  val mimeType: String,

  val status: String,
  val attempts: Int,
  val lastError: String? = null,

  val createdAtMs: Long,
  val updatedAtMs: Long,
)

object UploadStatus {
  const val PENDING = "PENDING"
  const val UPLOADING = "UPLOADING"
  const val SENT = "SENT"
  const val FAILED = "FAILED"
}

