package com.ssltd.fieldapp.data.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update
import kotlinx.coroutines.flow.Flow

@Dao
interface QueuedUploadDao {
  @Query("SELECT * FROM queued_uploads ORDER BY createdAtMs DESC")
  fun observeAll(): Flow<List<QueuedUpload>>

  @Query("SELECT * FROM queued_uploads WHERE id = :id LIMIT 1")
  suspend fun getById(id: Long): QueuedUpload?

  @Insert(onConflict = OnConflictStrategy.ABORT)
  suspend fun insert(item: QueuedUpload): Long

  @Update
  suspend fun update(item: QueuedUpload): Int

  @Query("UPDATE queued_uploads SET status = :status, attempts = :attempts, lastError = :lastError, updatedAtMs = :updatedAtMs WHERE id = :id")
  suspend fun updateState(id: Long, status: String, attempts: Int, lastError: String?, updatedAtMs: Long): Int

  @Query("DELETE FROM queued_uploads WHERE id = :id")
  suspend fun deleteById(id: Long): Int

  @Query("SELECT id FROM queued_uploads WHERE status IN ('PENDING','FAILED') ORDER BY createdAtMs ASC LIMIT :limit")
  suspend fun listPendingIds(limit: Int = 50): List<Long>
}

