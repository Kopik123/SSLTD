package com.ssltd.fieldapp.ui.screens

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
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
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import com.ssltd.fieldapp.data.AuthStore
import com.ssltd.fieldapp.data.ExifSanitizer
import com.ssltd.fieldapp.data.PendingFiles
import com.ssltd.fieldapp.data.api.ApiProject
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.data.db.AppDb
import com.ssltd.fieldapp.data.db.QueuedUpload
import com.ssltd.fieldapp.data.db.UploadStatus
import com.ssltd.fieldapp.work.UploadWork
import kotlinx.coroutines.launch
import java.io.File

@Composable
fun CaptureScreen(
  api: SsApi,
  authStore: AuthStore,
) {
  val context = LocalContext.current
  val scope = rememberCoroutineScope()
  val db = remember { AppDb.get(context) }
  val dao = remember { db.queuedUploadDao() }

  val uploads by dao.observeAll().collectAsState(initial = emptyList())

  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var projects by remember { mutableStateOf<List<ApiProject>>(emptyList()) }
  var selectedProject by remember { mutableStateOf<ApiProject?>(null) }
  var stage by remember { mutableStateOf("before") }
  var notes by remember { mutableStateOf("") }

  val allowedMimes = remember { setOf("image/jpeg", "image/png", "application/pdf") }

  suspend fun loadProjects() {
    loading = true
    error = null
    try {
      projects = api.listProjects().items
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load projects."
    } finally {
      loading = false
    }
  }

  suspend fun enqueueUpload(file: File, originalName: String, mimeType: String) {
    val p = selectedProject
    if (p == null) {
      error = "Select a project first."
      try { file.delete() } catch (_: Throwable) { /* ignore */ }
      return
    }

    val now = System.currentTimeMillis()
    val id = dao.insert(
      QueuedUpload(
        ownerType = "project",
        ownerId = p.id,
        stage = stage,
        filePath = file.absolutePath,
        originalName = originalName,
        mimeType = mimeType,
        status = UploadStatus.PENDING,
        attempts = 0,
        lastError = null,
        createdAtMs = now,
        updatedAtMs = now,
      )
    )
    UploadWork.enqueue(context, id)
  }

  LaunchedEffect(Unit) {
    if (authStore.token().isNullOrBlank()) {
      error = "Not authenticated."
      loading = false
      return@LaunchedEffect
    }
    loadProjects()
  }

  var pendingCameraFile by remember { mutableStateOf<File?>(null) }
  val cameraLauncher = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { ok ->
    val f = pendingCameraFile
    pendingCameraFile = null
    if (!ok || f == null) {
      if (f != null) {
        try { f.delete() } catch (_: Throwable) { /* ignore */ }
      }
      return@rememberLauncherForActivityResult
    }

    scope.launch {
      try {
        ExifSanitizer.stripGpsFromJpeg(f)
        enqueueUpload(file = f, originalName = f.name, mimeType = "image/jpeg")
      } catch (t: Throwable) {
        error = t.message ?: "Failed to queue upload."
        try { f.delete() } catch (_: Throwable) { /* ignore */ }
      }
    }
  }

  val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
    if (!granted) {
      error = "Camera permission denied."
      return@rememberLauncherForActivityResult
    }
    val f = PendingFiles.newPendingJpegFile(context)
    val uri = FileProvider.getUriForFile(context, context.packageName + ".fileprovider", f)
    pendingCameraFile = f
    cameraLauncher.launch(uri)
  }

  val pickLauncher = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri: Uri? ->
    if (uri == null) return@rememberLauncherForActivityResult
    scope.launch {
      try {
        val copied = PendingFiles.copyUriToPending(context, uri, allowedMimes)
        enqueueUpload(file = copied.file, originalName = copied.originalName, mimeType = copied.mimeType)
      } catch (t: Throwable) {
        error = t.message ?: "Failed to queue upload."
      }
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
          text = "Capture",
          style = MaterialTheme.typography.headlineSmall,
          color = MaterialTheme.colorScheme.onBackground,
        )
      }

      if (!error.isNullOrBlank()) {
        item { Text(text = error ?: "", color = MaterialTheme.colorScheme.error) }
      }

      if (loading) {
        item { Text(text = "Loading...", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
      }

      if (!loading) {
        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(PaddingValues(14.dp))) {
              Text(text = "Project", style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.onSurface)
              Text(
                text = selectedProject?.name ?: "None selected",
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
                modifier = Modifier.padding(top = 6.dp),
              )
              if (selectedProject == null) {
                Text(
                  text = "Pick one below.",
                  color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f),
                  modifier = Modifier.padding(top = 6.dp),
                )
              } else {
                Button(
                  onClick = { selectedProject = null },
                  modifier = Modifier.padding(top = 10.dp),
                ) { Text("Change project") }
              }
            }
          }
        }

        if (selectedProject == null) {
          if (projects.isEmpty()) {
            item { Text(text = "No assigned projects.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
          } else {
            items(projects) { p ->
              Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(PaddingValues(14.dp))) {
                  Text(text = p.name, color = MaterialTheme.colorScheme.onSurface, style = MaterialTheme.typography.titleMedium)
                  Text(
                    text = "${p.status}  •  ${p.address}",
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.padding(top = 4.dp),
                  )
                  Button(
                    onClick = { selectedProject = p },
                    modifier = Modifier.padding(top = 10.dp),
                  ) { Text("Select") }
                }
              }
            }
          }
        } else {
          item {
            Card(modifier = Modifier.fillMaxWidth()) {
              Column(modifier = Modifier.padding(PaddingValues(14.dp))) {
                Text(text = "Stage", style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.onSurface)
                Row(
                  modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 10.dp),
                  horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                  Button(onClick = { stage = "before" }, enabled = stage != "before") { Text("Before") }
                  Button(onClick = { stage = "during" }, enabled = stage != "during") { Text("During") }
                  Button(onClick = { stage = "after" }, enabled = stage != "after") { Text("After") }
                }
                Row(
                  modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 8.dp),
                  horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                  Button(onClick = { stage = "doc" }, enabled = stage != "doc") { Text("Doc") }
                }

                OutlinedTextField(
                  value = notes,
                  onValueChange = { notes = it },
                  label = { Text("Notes (local only for MVP)") },
                  modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                  minLines = 2,
                )

                Row(
                  modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                  horizontalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                  Button(
                    onClick = {
                      error = null
                      permissionLauncher.launch(android.Manifest.permission.CAMERA)
                    },
                  ) { Text("Take photo") }
                  Button(
                    onClick = {
                      error = null
                      pickLauncher.launch(arrayOf("image/jpeg", "image/png", "application/pdf"))
                    },
                  ) { Text("Pick file") }
                }
              }
            }
          }

          item {
            Text(
              text = "Upload queue",
              style = MaterialTheme.typography.titleMedium,
              color = MaterialTheme.colorScheme.onBackground,
            )
          }

          if (uploads.isEmpty()) {
            item { Text(text = "Queue is empty.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
          } else {
            items(uploads) { u ->
              Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(PaddingValues(14.dp))) {
                  Text(text = u.originalName, color = MaterialTheme.colorScheme.onSurface, style = MaterialTheme.typography.titleSmall)
                  Text(
                    text = "Status: ${u.status}" + (if (!u.lastError.isNullOrBlank()) " • ${u.lastError}" else ""),
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(top = 6.dp),
                  )
                  Row(
                    modifier = Modifier
                      .fillMaxWidth()
                      .padding(top = 10.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                  ) {
                    Button(
                      onClick = {
                        scope.launch {
                          dao.updateState(u.id, UploadStatus.PENDING, u.attempts, u.lastError, System.currentTimeMillis())
                          UploadWork.enqueue(context, u.id)
                        }
                      },
                      enabled = u.status != UploadStatus.UPLOADING,
                    ) { Text("Retry") }
                    Button(
                      onClick = {
                        scope.launch {
                          dao.deleteById(u.id)
                          try { File(u.filePath).delete() } catch (_: Throwable) { /* ignore */ }
                        }
                      },
                      enabled = u.status != UploadStatus.UPLOADING,
                    ) { Text("Delete") }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
