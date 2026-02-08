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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import com.ssltd.fieldapp.data.ExifSanitizer
import com.ssltd.fieldapp.data.PendingFiles
import com.ssltd.fieldapp.data.api.ApiMessage
import com.ssltd.fieldapp.data.api.ApiProject
import com.ssltd.fieldapp.data.api.SendMessageRequest
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.data.db.AppDb
import com.ssltd.fieldapp.data.db.CachedMessage
import com.ssltd.fieldapp.data.db.CachedProject
import com.ssltd.fieldapp.data.db.CachedThread
import com.ssltd.fieldapp.data.db.QueuedUpload
import com.ssltd.fieldapp.data.db.UploadStatus
import com.ssltd.fieldapp.work.UploadWork
import kotlinx.coroutines.launch
import java.io.File

@Composable
fun MessagesScreen(api: SsApi, isOnline: Boolean) {
  val scope = rememberCoroutineScope()
  val context = LocalContext.current
  val db = remember { AppDb.get(context) }
  val dao = remember { db.queuedUploadDao() }
  val projDao = remember { db.cachedProjectDao() }
  val threadDao = remember { db.cachedThreadDao() }
  val msgDao = remember { db.cachedMessageDao() }
  val allowedMimes = remember { setOf("image/jpeg", "image/png", "application/pdf") }

  var projects by remember { mutableStateOf<List<ApiProject>>(emptyList()) }
  var selectedProject by remember { mutableStateOf<ApiProject?>(null) }
  var threadId by remember { mutableStateOf<Int?>(null) }
  var messages by remember { mutableStateOf<List<ApiMessage>>(emptyList()) }
  var body by remember { mutableStateOf("") }
  var loading by remember { mutableStateOf(true) }
  var busy by remember { mutableStateOf(false) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }

  suspend fun loadProjectsFromCache() {
    val cached = projDao.listAll()
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

  suspend fun saveProjectsToCache(items: List<ApiProject>) {
    val now = System.currentTimeMillis()
    projDao.upsertAll(items.map { p ->
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
    })
  }

  suspend fun loadThreadFromCache(projectId: Int): Int? {
    val threads = threadDao.listByScope(scopeType = "project", scopeId = projectId)
    return threads.firstOrNull()?.id
  }

  suspend fun saveThreadsToCache(items: List<com.ssltd.fieldapp.data.api.ApiThread>) {
    val now = System.currentTimeMillis()
    threadDao.upsertAll(items.map { t ->
      CachedThread(
        id = t.id,
        scopeType = t.scopeType,
        scopeId = t.scopeId,
        createdAt = t.createdAt,
        cachedAtMs = now,
      )
    })
  }

  suspend fun loadMessagesFromCache(tid: Int) {
    val cached = msgDao.listByThread(tid)
    messages = cached.map { m ->
      ApiMessage(
        id = m.id,
        threadId = m.threadId,
        senderUserId = m.senderUserId,
        body = m.body,
        createdAt = m.createdAt,
        senderName = m.senderName,
        senderRole = m.senderRole,
      )
    }
  }

  suspend fun saveMessagesToCache(tid: Int, items: List<ApiMessage>) {
    val now = System.currentTimeMillis()
    // Replace thread cache for deterministic ordering.
    msgDao.clearThread(tid)
    msgDao.upsertAll(items.map { m ->
      CachedMessage(
        id = m.id,
        threadId = m.threadId,
        senderUserId = m.senderUserId,
        senderName = m.senderName,
        senderRole = m.senderRole,
        body = m.body,
        createdAt = m.createdAt,
        cachedAtMs = now,
      )
    })
  }

  suspend fun loadProjects() {
    loading = true
    error = null
    notice = null
    try {
      if (isOnline) {
        val items = api.listProjects().items
        projects = items
        saveProjectsToCache(items)
      } else {
        notice = "Offline: showing cached projects."
        loadProjectsFromCache()
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load projects."
      try {
        notice = "Showing cached projects."
        loadProjectsFromCache()
      } catch (_: Throwable) {
        // ignore
      }
    } finally {
      loading = false
    }
  }

  suspend fun openProject(project: ApiProject) {
    selectedProject = project
    error = null
    notice = null
    messages = emptyList()
    threadId = null
    try {
      if (isOnline) {
        val threads = api.listThreads(scope = "project", scopeId = project.id).items
        saveThreadsToCache(threads)
        val tid = threads.firstOrNull()?.id
        if (tid == null) {
          error = "No thread available."
          return
        }
        threadId = tid
        val items = api.getMessages(id = tid, after = null, limit = 200).items
        messages = items
        saveMessagesToCache(tid, items)
      } else {
        notice = "Offline: showing cached thread/messages (if available)."
        val tid = loadThreadFromCache(project.id)
        if (tid == null) {
          error = "Offline and no cached thread available."
          return
        }
        threadId = tid
        loadMessagesFromCache(tid)
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to open thread."
      try {
        val tid = loadThreadFromCache(project.id)
        if (tid != null) {
          notice = "Showing cached thread/messages."
          threadId = tid
          loadMessagesFromCache(tid)
        }
      } catch (_: Throwable) {
        // ignore
      }
    }
  }

  suspend fun refreshMessages() {
    if (!isOnline) return
    val tid = threadId ?: return
    error = null
    try {
      val after = messages.lastOrNull()?.createdAt
      val newItems = api.getMessages(id = tid, after = after, limit = 200).items
      if (newItems.isNotEmpty()) {
        messages = messages + newItems
        // Best-effort: store merged view.
        try {
          saveMessagesToCache(tid, messages)
        } catch (_: Throwable) {
          // ignore
        }
      }
    } catch (t: Throwable) {
      error = t.message ?: "Failed to refresh messages."
    }
  }

  suspend fun enqueueAttachment(file: File, originalName: String, mimeType: String) {
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
        stage = "doc",
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
    notice = "Attachment queued for upload."
  }

  LaunchedEffect(Unit) {
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
        enqueueAttachment(file = f, originalName = f.name, mimeType = "image/jpeg")
      } catch (t: Throwable) {
        error = t.message ?: "Failed to queue attachment."
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
        enqueueAttachment(file = copied.file, originalName = copied.originalName, mimeType = copied.mimeType)
      } catch (t: Throwable) {
        error = t.message ?: "Failed to queue attachment."
      }
    }
  }

  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    Column(modifier = Modifier.fillMaxSize()) {
      // Header
      Row(
        modifier = Modifier
          .fillMaxWidth()
          .padding(PaddingValues(16.dp)),
        horizontalArrangement = Arrangement.SpaceBetween,
      ) {
        Text(
          text = "Messages",
          style = MaterialTheme.typography.headlineSmall,
          color = MaterialTheme.colorScheme.onBackground,
        )
        if (selectedProject != null) {
          Button(
            onClick = {
              selectedProject = null
              threadId = null
              messages = emptyList()
              error = null
              body = ""
            },
            enabled = !busy,
          ) {
            Text("Change")
          }
        }
      }

      if (!error.isNullOrBlank()) {
        Text(
          text = error ?: "",
          color = MaterialTheme.colorScheme.error,
          modifier = Modifier.padding(PaddingValues(start = 16.dp, end = 16.dp, bottom = 8.dp)),
        )
      }

      if (!notice.isNullOrBlank()) {
        Text(
          text = notice ?: "",
          color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.8f),
          modifier = Modifier.padding(PaddingValues(start = 16.dp, end = 16.dp, bottom = 8.dp)),
        )
      }

      if (!isOnline && selectedProject != null) {
        Text(
          text = "Offline: sending messages is disabled (attachments still queue).",
          color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.8f),
          modifier = Modifier.padding(PaddingValues(start = 16.dp, end = 16.dp, bottom = 8.dp)),
        )
      }

      if (loading) {
        Text(
          text = "Loading...",
          color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f),
          modifier = Modifier.padding(PaddingValues(16.dp)),
        )
      } else if (selectedProject == null) {
        if (projects.isEmpty()) {
          Text(
            text = "No assigned projects.",
            color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f),
            modifier = Modifier.padding(PaddingValues(16.dp)),
          )
        } else {
          LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
          ) {
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
                    onClick = {
                      scope.launch { openProject(p) }
                    },
                    modifier = Modifier.padding(top = 10.dp),
                  ) {
                    Text("Open thread")
                  }
                }
              }
            }
          }
        }
      } else {
        // Thread view
        Text(
          text = selectedProject?.name ?: "",
          modifier = Modifier.padding(PaddingValues(start = 16.dp, end = 16.dp, bottom = 8.dp)),
          color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.85f),
          style = MaterialTheme.typography.titleMedium,
        )

        LazyColumn(
          modifier = Modifier
            .weight(1f)
            .fillMaxWidth(),
          contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 12.dp),
          verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
          if (messages.isEmpty()) {
            item {
              Text(
                text = "No messages yet.",
                color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f),
              )
            }
          }

          items(messages) { m ->
            Card(modifier = Modifier.fillMaxWidth()) {
              Column(modifier = Modifier.padding(PaddingValues(12.dp))) {
                Text(
                  text = (m.senderName ?: "System") + (if (!m.senderRole.isNullOrBlank()) " (${m.senderRole})" else ""),
                  color = MaterialTheme.colorScheme.onSurface,
                  style = MaterialTheme.typography.titleSmall,
                )
                Text(
                  text = m.body,
                  color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.9f),
                  style = MaterialTheme.typography.bodyMedium,
                  modifier = Modifier.padding(top = 6.dp),
                )
                Text(
                  text = m.createdAt,
                  color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                  style = MaterialTheme.typography.bodySmall,
                  modifier = Modifier.padding(top = 6.dp),
                )
              }
            }
          }
        }

        // Composer
        Row(
          modifier = Modifier
            .fillMaxWidth()
            .padding(PaddingValues(16.dp)),
          horizontalArrangement = Arrangement.spacedBy(10.dp),
        ) {
          Button(
            onClick = {
              notice = null
              error = null
              permissionLauncher.launch(android.Manifest.permission.CAMERA)
            },
            enabled = !busy,
          ) { Text("Photo") }

          Button(
            onClick = {
              notice = null
              error = null
              pickLauncher.launch(arrayOf("image/jpeg", "image/png", "application/pdf"))
            },
            enabled = !busy,
          ) { Text("File") }

          OutlinedTextField(
            value = body,
            onValueChange = { body = it },
            label = { Text("Message") },
            modifier = Modifier.weight(1f),
            enabled = !busy,
          )
          Button(
            onClick = {
              if (!isOnline) {
                notice = "Offline: message sending disabled."
                return@Button
              }
              val tid = threadId ?: return@Button
              if (busy) return@Button
              val msg = body.trim()
              if (msg.isEmpty()) return@Button
              busy = true
              error = null
              notice = null
              scope.launch {
                try {
                  api.sendMessage(id = tid, req = SendMessageRequest(body = msg))
                  body = ""
                  refreshMessages()
                } catch (t: Throwable) {
                  error = t.message ?: "Failed to send."
                } finally {
                  busy = false
                }
              }
            },
            enabled = !busy && isOnline,
          ) {
            Text(if (busy) "..." else "Send")
          }
        }
      }
    }
  }
}
