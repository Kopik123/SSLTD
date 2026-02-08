package com.ssltd.fieldapp.data.db

import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "cached_projects")
data class CachedProject(
  @PrimaryKey val id: Int,
  val status: String,
  val name: String,
  val address: String,
  val budgetCents: Long,
  val clientName: String?,
  val clientEmail: String?,
  val pmName: String?,
  val createdAt: String,
  val updatedAt: String,
  val cachedAtMs: Long,
)

