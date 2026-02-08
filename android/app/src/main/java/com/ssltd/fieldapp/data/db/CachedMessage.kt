package com.ssltd.fieldapp.data.db

import androidx.room.Entity
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
  tableName = "cached_messages",
  indices = [
    Index(value = ["threadId", "createdAt"]),
  ],
)
data class CachedMessage(
  @PrimaryKey val id: Int,
  val threadId: Int,
  val senderUserId: Int?,
  val senderName: String?,
  val senderRole: String?,
  val body: String,
  val createdAt: String,
  val cachedAtMs: Long,
)

