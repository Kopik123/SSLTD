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
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.data.api.CreateProjectReportRequest
import com.ssltd.fieldapp.data.api.SsApi
import kotlinx.coroutines.launch

@Composable
fun AddReportScreen(
  api: SsApi,
  projectId: Int,
  isOnline: Boolean,
  onBack: () -> Unit,
) {
  val scope = rememberCoroutineScope()
  var body by remember { mutableStateOf("") }
  var busy by remember { mutableStateOf(false) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }

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
            text = "Add report",
            style = MaterialTheme.typography.headlineSmall,
            color = MaterialTheme.colorScheme.onBackground,
          )
          Button(onClick = onBack) { Text("Back") }
        }
      }

      if (!isOnline) {
        item {
          Text(
            text = "Offline: report submission requires internet (for now).",
            color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f),
          )
        }
      }

      if (!error.isNullOrBlank()) {
        item { Text(text = error ?: "", color = MaterialTheme.colorScheme.error) }
      }
      if (!notice.isNullOrBlank()) {
        item { Text(text = notice ?: "", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      item {
        Card(modifier = Modifier.fillMaxWidth()) {
          Text(
            text = "Project #$projectId",
            modifier = Modifier.padding(PaddingValues(14.dp)),
            color = MaterialTheme.colorScheme.onSurface,
            style = MaterialTheme.typography.titleMedium,
          )
          OutlinedTextField(
            value = body,
            onValueChange = { body = it },
            modifier = Modifier.fillMaxWidth().padding(horizontal = 14.dp, vertical = 10.dp),
            label = { Text("Update") },
            placeholder = { Text("What happened? What's next? Any blockers?") },
            minLines = 4,
          )
          Button(
            onClick = {
              if (busy) return@Button
              error = null
              notice = null
              val trimmed = body.trim()
              if (!isOnline) {
                error = "Offline."
                return@Button
              }
              if (trimmed.isEmpty()) {
                error = "Report is empty."
                return@Button
              }
              busy = true
              scope.launch {
                try {
                  api.createProjectReport(projectId, CreateProjectReportRequest(body = trimmed))
                  notice = "Report submitted."
                  body = ""
                } catch (t: Throwable) {
                  error = t.message ?: "Failed to submit."
                } finally {
                  busy = false
                }
              }
            },
            enabled = !busy && isOnline,
            modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
          ) {
            Text(if (busy) "Submitting..." else "Submit")
          }
        }
      }
    }
  }
}

