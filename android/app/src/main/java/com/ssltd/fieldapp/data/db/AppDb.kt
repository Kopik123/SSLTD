package com.ssltd.fieldapp.data.db

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(
  entities = [
    QueuedUpload::class,
    CachedProject::class,
    CachedThread::class,
    CachedMessage::class,
    CachedScheduleEvent::class,
  ],
  version = 3,
  exportSchema = false,
)
abstract class AppDb : RoomDatabase() {
  abstract fun queuedUploadDao(): QueuedUploadDao
  abstract fun cachedProjectDao(): CachedProjectDao
  abstract fun cachedThreadDao(): CachedThreadDao
  abstract fun cachedMessageDao(): CachedMessageDao
  abstract fun cachedScheduleEventDao(): CachedScheduleEventDao

  companion object {
    @Volatile private var instance: AppDb? = null

    fun get(context: Context): AppDb {
      return instance ?: synchronized(this) {
        instance ?: Room.databaseBuilder(context.applicationContext, AppDb::class.java, "ss_field.db")
          .fallbackToDestructiveMigration()
          .build()
          .also { instance = it }
      }
    }
  }
}
