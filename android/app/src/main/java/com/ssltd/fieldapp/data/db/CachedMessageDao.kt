package com.ssltd.fieldapp.data.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface CachedMessageDao {
  @Query("SELECT * FROM cached_messages WHERE threadId = :threadId ORDER BY createdAt ASC")
  suspend fun listByThread(threadId: Int): List<CachedMessage>

  @Insert(onConflict = OnConflictStrategy.REPLACE)
  suspend fun upsertAll(items: List<CachedMessage>)

  @Query("DELETE FROM cached_messages WHERE threadId = :threadId")
  suspend fun clearThread(threadId: Int)
}

