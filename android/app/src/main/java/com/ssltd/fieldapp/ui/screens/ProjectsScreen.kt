package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Card
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.data.api.ApiProject
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.data.db.AppDb
import com.ssltd.fieldapp.data.db.CachedProject

@Composable
fun ProjectsScreen(
  api: SsApi,
  isOnline: Boolean,
  onOpenProject: (Int) -> Unit,
) {
  val context = LocalContext.current
  val db = remember { AppDb.get(context) }
  val cacheDao = remember { db.cachedProjectDao() }

  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }
  var projects by remember { mutableStateOf<List<ApiProject>>(emptyList()) }

  suspend fun loadFromCache() {
    val cached = cacheDao.listAll()
    projects = cached.map { c ->
      ApiProject(
        id = c.id,
        status = c.status,
        name = c.name,
        address = c.address,
        budgetCents = c.budgetCents,
        clientName = c.clientName,
        clientEmail = c.clientEmail,
        createdAt = c.createdAt,
        updatedAt = c.updatedAt,
        pmName = c.pmName,
      )
    }
  }

  suspend fun saveToCache(items: List<ApiProject>) {
    val now = System.currentTimeMillis()
    val rows = items.map { p ->
      CachedProject(
        id = p.id,
        status = p.status,
        name = p.name,
        address = p.address,
        budgetCents = p.budgetCents,
        clientName = p.clientName,
        clientEmail = p.clientEmail,
        pmName = p.pmName,
        createdAt = p.createdAt,
        updatedAt = p.updatedAt,
        cachedAtMs = now,
      )
    }
    cacheDao.upsertAll(rows)
  }

  LaunchedEffect(isOnline) {
    loading = true
    error = null
    notice = null
    try {
      if (isOnline) {
        val items = api.listProjects().items
        projects = items
        saveToCache(items)
      } else {
        notice = "Offline: showing cached projects."
        loadFromCache()
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load projects."
      // Best-effort fallback to cache so the app stays usable.
      try {
        notice = "Showing cached projects."
        loadFromCache()
      } catch (_: Throwable) {
        // ignore
      }
    } finally {
      loading = false
    }
  }

  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    LazyColumn(
      modifier = Modifier.fillMaxSize(),
      contentPadding = PaddingValues(16.dp),
      verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
      item {
        Text(
          text = "Projects",
          style = MaterialTheme.typography.headlineSmall,
          color = MaterialTheme.colorScheme.onBackground,
        )
      }

      if (loading) {
        item { Text(text = "Loading...", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      if (!error.isNullOrBlank()) {
        item { Text(text = error ?: "", color = MaterialTheme.colorScheme.error) }
      }

      if (!notice.isNullOrBlank()) {
        item { Text(text = notice ?: "", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      if (!loading && error.isNullOrBlank() && projects.isEmpty()) {
        item { Text(text = "No assigned projects.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      items(projects) { p ->
        Card(modifier = Modifier.fillMaxWidth().clickable { onOpenProject(p.id) }) {
          Text(
            text = p.name,
            modifier = Modifier.padding(PaddingValues(14.dp)),
            color = MaterialTheme.colorScheme.onSurface,
            style = MaterialTheme.typography.titleMedium,
          )
          Text(
            text = "${p.status}  •  ${p.address}",
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
            style = MaterialTheme.typography.bodyMedium,
          )
        }
      }
    }
  }
}
