package com.ssltd.fieldapp.data.db

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface CachedProjectDao {
  @Query("SELECT * FROM cached_projects ORDER BY updatedAt DESC")
  suspend fun listAll(): List<CachedProject>

  @Query("SELECT * FROM cached_projects WHERE id = :id LIMIT 1")
  suspend fun getById(id: Int): CachedProject?

  @Insert(onConflict = OnConflictStrategy.REPLACE)
  suspend fun upsertAll(items: List<CachedProject>)

  @Insert(onConflict = OnConflictStrategy.REPLACE)
  suspend fun upsertOne(item: CachedProject)

  @Query("DELETE FROM cached_projects")
  suspend fun clear()
}

