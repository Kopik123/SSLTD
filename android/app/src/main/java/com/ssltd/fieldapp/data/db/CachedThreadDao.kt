package com.ssltd.fieldapp.data.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface CachedThreadDao {
  @Query("SELECT * FROM cached_threads WHERE scopeType = :scopeType AND scopeId = :scopeId ORDER BY createdAt ASC")
  suspend fun listByScope(scopeType: String, scopeId: Int): List<CachedThread>

  @Insert(onConflict = OnConflictStrategy.REPLACE)
  suspend fun upsertAll(items: List<CachedThread>)

  @Query("DELETE FROM cached_threads")
  suspend fun clear()
}

