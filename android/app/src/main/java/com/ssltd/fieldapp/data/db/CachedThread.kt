package com.ssltd.fieldapp.data.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "cached_threads")
data class CachedThread(
  @PrimaryKey val id: Int,
  val scopeType: String,
  val scopeId: Int,
  val createdAt: String,
  val cachedAtMs: Long,
)

