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
import androidx.compose.material3.OutlinedTextField
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
import com.ssltd.fieldapp.data.api.ApiIssue
import com.ssltd.fieldapp.data.api.CreateIssueRequest
import com.ssltd.fieldapp.data.api.SsApi
import kotlinx.coroutines.launch

@Composable
fun IssuesScreen(
  api: SsApi,
  projectId: Int,
  isOnline: Boolean,
  onBack: () -> Unit,
) {
  val scope = rememberCoroutineScope()
  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }
  var items by remember { mutableStateOf<List<ApiIssue>>(emptyList()) }

  var newTitle by remember { mutableStateOf("") }
  var newBody by remember { mutableStateOf("") }
  var busy by remember { mutableStateOf(false) }

  suspend fun refresh() {
    loading = true
    error = null
    notice = null
    try {
      if (!isOnline) {
        notice = "Offline: issues require internet (for now)."
        items = emptyList()
      } else {
        items = api.getProjectIssues(projectId, limit = 100).items
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load issues."
    } finally {
      loading = false
    }
  }

  LaunchedEffect(projectId, isOnline) {
    refresh()
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
            text = "Issues",
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

      item {
        Card(modifier = Modifier.fillMaxWidth()) {
          Text(
            text = "New issue",
            modifier = Modifier.padding(PaddingValues(14.dp)),
            color = MaterialTheme.colorScheme.onSurface,
            style = MaterialTheme.typography.titleMedium,
          )
          OutlinedTextField(
            value = newTitle,
            onValueChange = { newTitle = it },
            modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 8.dp),
            label = { Text("Title") },
            placeholder = { Text("Short issue title") },
            singleLine = true,
          )
          OutlinedTextField(
            value = newBody,
            onValueChange = { newBody = it },
            modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 8.dp),
            label = { Text("Details (optional)") },
            minLines = 3,
          )
          Button(
            onClick = {
              if (busy) return@Button
              error = null
              notice = null
              if (!isOnline) {
                error = "Offline."
                return@Button
              }
              val t = newTitle.trim()
              if (t.isEmpty()) {
                error = "Title is required."
                return@Button
              }
              busy = true
              scope.launch {
                try {
                  api.createIssue(projectId, CreateIssueRequest(title = t, body = newBody.trim().ifEmpty { null }))
                  newTitle = ""
                  newBody = ""
                  refresh()
                } catch (e: Throwable) {
                  error = e.message ?: "Failed to create issue."
                } finally {
                  busy = false
                }
              }
            },
            enabled = !busy && isOnline,
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
          ) {
            Text(if (busy) "Creating..." else "Create")
          }
        }
      }

      item {
        Text(
          text = "List",
          style = MaterialTheme.typography.titleMedium,
          color = MaterialTheme.colorScheme.onBackground,
        )
      }

      if (!loading && items.isEmpty() && error.isNullOrBlank()) {
        item { Text(text = "No issues.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      items(items) { it ->
        Card(modifier = Modifier.fillMaxWidth()) {
          Text(
            text = it.title,
            modifier = Modifier.padding(PaddingValues(14.dp)),
            color = MaterialTheme.colorScheme.onSurface,
            style = MaterialTheme.typography.titleMedium,
          )
          Text(
            text = "${it.severity}  •  ${it.status}",
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 6.dp)),
            color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
            style = MaterialTheme.typography.bodyMedium,
          )
          val b = it.body
          if (!b.isNullOrBlank()) {
            Text(
              text = b,
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
              style = MaterialTheme.typography.bodyMedium,
            )
          }
        }
      }
    }
  }
}
