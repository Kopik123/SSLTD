package com.ssltd.fieldapp.data.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface CachedScheduleEventDao {
  @Query("SELECT * FROM cached_schedule_events WHERE startsAt >= :from AND startsAt <= :to ORDER BY startsAt ASC")
  suspend fun listRange(from: String, to: String): List<CachedScheduleEvent>

  @Insert(onConflict = OnConflictStrategy.REPLACE)
  suspend fun upsertAll(items: List<CachedScheduleEvent>)

  @Query("DELETE FROM cached_schedule_events")
  suspend fun clear()
}

