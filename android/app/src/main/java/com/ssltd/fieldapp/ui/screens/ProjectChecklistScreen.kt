package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.data.api.ApiChecklistItem
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.data.api.UpdateChecklistItemRequest
import kotlinx.coroutines.launch

@Composable
fun ProjectChecklistScreen(
  api: SsApi,
  projectId: Int,
  isOnline: Boolean,
  onBack: () -> Unit,
) {
  val scope = rememberCoroutineScope()
  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }
  var items by remember { mutableStateOf<List<ApiChecklistItem>>(emptyList()) }
  var busyItemId by remember { mutableStateOf<Int?>(null) }

  suspend fun load() {
    loading = true
    error = null
    notice = null
    try {
      val res = api.getProjectChecklist(projectId)
      items = res.items
      if (!isOnline) {
        notice = "Offline: checklist updates are disabled."
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load checklist."
      items = emptyList()
    } finally {
      loading = false
    }
  }

  LaunchedEffect(projectId) {
    load()
  }

  fun nextStatus(cur: String): String {
    return when (cur) {
      "todo" -> "in_progress"
      "in_progress" -> "done"
      "done" -> "todo"
      "blocked" -> "todo"
      else -> "todo"
    }
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
            text = "Checklist (Project #$projectId)",
            style = MaterialTheme.typography.headlineSmall,
            color = MaterialTheme.colorScheme.onBackground,
          )
          Button(onClick = onBack) { Text("Back") }
        }
      }

      if (!error.isNullOrBlank()) {
        item { Text(text = error ?: "", color = MaterialTheme.colorScheme.error) }
      }

      if (!notice.isNullOrBlank()) {
        item { Text(text = notice ?: "", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      if (loading) {
        item { Text(text = "Loading...", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      if (!loading && error.isNullOrBlank() && items.isEmpty()) {
        item { Text(text = "No checklist items.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      items(items) { it ->
        val busy = busyItemId == it.id
        Card(modifier = Modifier.fillMaxWidth()) {
          Text(
            text = it.title,
            modifier = Modifier.padding(PaddingValues(14.dp)),
            color = MaterialTheme.colorScheme.onSurface,
            style = MaterialTheme.typography.titleMedium,
          )
          Text(
            text = "Status: ${it.status}",
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 10.dp)),
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
            style = MaterialTheme.typography.bodyMedium,
          )
          Button(
            onClick = {
              if (!isOnline || busy) return@Button
              busyItemId = it.id
              val newStatus = nextStatus(it.status)
              // optimistic UI update
              items = items.map { row -> if (row.id == it.id) row.copy(status = newStatus) else row }
              scope.launch {
                try {
                  api.updateChecklistItemStatus(it.id, UpdateChecklistItemRequest(status = newStatus))
                } catch (_: Throwable) {
                  // revert on failure
                  items = items.map { row -> if (row.id == it.id) row.copy(status = it.status) else row }
                  error = "Failed to update status."
                } finally {
                  busyItemId = null
                }
              }
            },
            enabled = isOnline && !busy,
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
          ) {
            Text(if (!isOnline) "Offline" else if (busy) "..." else "Toggle status")
          }
        }
      }
    }
  }
}
