package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material3.Button
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
fun ProjectDetailScreen(
  api: SsApi,
  isOnline: Boolean,
  projectId: Int,
  onBack: () -> Unit,
  onOpenChecklist: () -> Unit,
  onAddReport: () -> Unit,
  onOpenIssues: () -> Unit,
) {
  val context = LocalContext.current
  val db = remember { AppDb.get(context) }
  val cacheDao = remember { db.cachedProjectDao() }

  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }
  var project by remember { mutableStateOf<ApiProject?>(null) }

  suspend fun loadFromCache(): ApiProject? {
    val c = cacheDao.getById(projectId) ?: return null
    return ApiProject(
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

  suspend fun saveToCache(p: ApiProject) {
    val now = System.currentTimeMillis()
    cacheDao.upsertOne(
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
    )
  }

  LaunchedEffect(projectId, isOnline) {
    loading = true
    error = null
    notice = null
    project = null
    try {
      if (isOnline) {
        val p = api.getProject(projectId).item
        project = p
        saveToCache(p)
      } else {
        notice = "Offline: showing cached project (if available)."
        project = loadFromCache()
        if (project == null) {
          error = "Offline and no cached project available."
        }
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load project."
      try {
        notice = "Showing cached project."
        project = loadFromCache()
      } catch (_: Throwable) {
        // ignore
      }
    } finally {
      loading = false
    }
  }

  fun money(cents: Long): String {
    val sign = if (cents < 0) "-" else ""
    val abs = kotlin.math.abs(cents)
    val dollars = abs / 100
    val rest = abs % 100
    return sign + "$" + dollars.toString() + "." + rest.toString().padStart(2, '0')
  }

  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    LazyColumn(
      modifier = Modifier.fillMaxSize(),
      contentPadding = PaddingValues(16.dp),
      verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
      item {
        Row(
          modifier = Modifier.fillMaxWidth(),
          horizontalArrangement = Arrangement.SpaceBetween,
        ) {
          Text(
            text = "Project #$projectId",
            style = MaterialTheme.typography.headlineSmall,
            color = MaterialTheme.colorScheme.onBackground,
          )
          Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Button(onClick = onOpenChecklist) { Text("Checklist") }
            Button(onClick = onAddReport) { Text("Report") }
            Button(onClick = onOpenIssues) { Text("Issues") }
            Button(onClick = onBack) { Text("Back") }
          }
        }
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

      val p = project
      if (!loading && error.isNullOrBlank() && p != null) {
        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = p.name,
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleLarge,
            )
            Text(
              text = p.address,
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
              style = MaterialTheme.typography.bodyMedium,
            )
          }
        }

        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = "Status: ${p.status}",
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleMedium,
            )
            Text(
              text = "Budget: ${money(p.budgetCents)}",
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
              style = MaterialTheme.typography.bodyMedium,
            )
          }
        }

        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = "Client",
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleMedium,
            )
            Text(
              text = (p.clientName ?: "-") + (if (!p.clientEmail.isNullOrBlank()) "  •  ${p.clientEmail}" else ""),
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
              style = MaterialTheme.typography.bodyMedium,
            )
          }
        }

        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = "Assigned PM",
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleMedium,
            )
            Text(
              text = p.pmName ?: "-",
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
              style = MaterialTheme.typography.bodyMedium,
            )
          }
        }

        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = "Created: ${p.createdAt}",
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
              style = MaterialTheme.typography.bodySmall,
            )
            Text(
              text = "Updated: ${p.updatedAt}",
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f),
              style = MaterialTheme.typography.bodySmall,
            )
          }
        }
      }
    }
  }
}
