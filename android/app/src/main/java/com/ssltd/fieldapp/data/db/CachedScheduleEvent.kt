package com.ssltd.fieldapp.data.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "cached_schedule_events")
data class CachedScheduleEvent(
  @PrimaryKey val id: Int,
  val projectId: Int,
  val projectName: String?,
  val title: String,
  val startsAt: String,
  val endsAt: String,
  val status: String,
  val cachedAtMs: Long,
)

