package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.PaddingValues
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.data.api.ApiProject
import com.ssltd.fieldapp.data.api.ApiScheduleEvent
import com.ssltd.fieldapp.data.api.ApiTimesheet
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.data.api.StartTimesheetRequest
import com.ssltd.fieldapp.data.api.StopTimesheetRequest
import com.ssltd.fieldapp.data.db.AppDb
import com.ssltd.fieldapp.data.db.CachedScheduleEvent
import kotlinx.coroutines.launch
import kotlinx.coroutines.delay
import java.time.Instant
import java.time.LocalDate
import java.time.OffsetDateTime
import java.time.format.DateTimeParseException
import kotlin.math.max

@Composable
fun TodayScreen(api: SsApi) {
  val context = LocalContext.current
  val db = remember { AppDb.get(context) }
  val scheduleDao = remember { db.cachedScheduleEventDao() }

  val scope = rememberCoroutineScope()
  var loading by remember { mutableStateOf(true) }
  var error by remember { mutableStateOf<String?>(null) }
  var notice by remember { mutableStateOf<String?>(null) }
  var projects by remember { mutableStateOf<List<ApiProject>>(emptyList()) }
  var timesheets by remember { mutableStateOf<List<ApiTimesheet>>(emptyList()) }
  var events by remember { mutableStateOf<List<ApiScheduleEvent>>(emptyList()) }
  var busy by remember { mutableStateOf(false) }

  fun todayKey(): String = LocalDate.now().toString() // YYYY-MM-DD

  fun defaultFrom(): String = todayKey() + "T00:00"
  fun defaultTo(): String = LocalDate.now().plusDays(7).toString() + "T23:59"

  suspend fun loadScheduleFromCache(from: String, to: String) {
    val cached = scheduleDao.listRange(from, to)
    events = cached.map { c ->
      ApiScheduleEvent(
        id = c.id,
        projectId = c.projectId,
        projectName = c.projectName,
        title = c.title,
        startsAt = c.startsAt,
        endsAt = c.endsAt,
        status = c.status,
      )
    }
  }

  suspend fun saveScheduleToCache(items: List<ApiScheduleEvent>) {
    val now = System.currentTimeMillis()
    val rows = items.map { e ->
      CachedScheduleEvent(
        id = e.id,
        projectId = e.projectId,
        projectName = e.projectName,
        title = e.title,
        startsAt = e.startsAt,
        endsAt = e.endsAt,
        status = e.status,
        cachedAtMs = now,
      )
    }
    scheduleDao.upsertAll(rows)
  }

  suspend fun refresh() {
    loading = true
    error = null
    notice = null
    try {
      projects = api.listProjects().items
      timesheets = api.listTimesheets().items
      val from = defaultFrom()
      val to = defaultTo()
      val sched = api.listSchedule(from = from, to = to, limit = 200).items
      events = sched
      saveScheduleToCache(sched)
    } catch (t: Throwable) {
      error = t.message ?: "Failed to load data."
      // Best-effort fallback to cache so Today stays useful offline.
      try {
        val from = defaultFrom()
        val to = defaultTo()
        notice = "Showing cached schedule."
        loadScheduleFromCache(from, to)
      } catch (_: Throwable) {
        // ignore
      }
    } finally {
      loading = false
    }
  }

  LaunchedEffect(Unit) {
    refresh()
  }

  val running = timesheets.firstOrNull { it.stoppedAt == null }
  var nowMs by remember { mutableStateOf(System.currentTimeMillis()) }

  LaunchedEffect(running?.startedAt) {
    if (running == null) return@LaunchedEffect
    while (true) {
      delay(1000)
      nowMs = System.currentTimeMillis()
    }
  }

  fun parseInstant(s: String): Instant? {
    val t = s.trim()
    if (t.isEmpty()) return null
    return try {
      OffsetDateTime.parse(t).toInstant()
    } catch (_: DateTimeParseException) {
      try {
        Instant.parse(t)
      } catch (_: DateTimeParseException) {
        null
      }
    }
  }

  fun formatElapsed(startedAt: String): String {
    val started = parseInstant(startedAt) ?: return "-"
    val elapsedMs = max(0L, nowMs - started.toEpochMilli())
    val totalSeconds = elapsedMs / 1000
    val h = totalSeconds / 3600
    val m = (totalSeconds % 3600) / 60
    val sec = totalSeconds % 60
    return h.toString().padStart(2, '0') + ":" +
      m.toString().padStart(2, '0') + ":" +
      sec.toString().padStart(2, '0')
  }

  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    LazyColumn(
      modifier = Modifier.fillMaxSize(),
      contentPadding = PaddingValues(16.dp),
      verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
      item {
        Text(
          text = "Today",
          style = MaterialTheme.typography.headlineSmall,
          color = MaterialTheme.colorScheme.onBackground,
        )
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

      if (!loading) {
        item {
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = if (running == null) "No active timesheet." else "Timesheet running",
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleMedium,
            )
            if (running != null) {
              val projName = projects.firstOrNull { it.id == (running.projectId ?: -1) }?.name
              Text(
                text = (if (projName.isNullOrBlank()) "Project #${running.projectId ?: "?"}" else projName) +
                  " • started ${running.startedAt}",
                modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
                style = MaterialTheme.typography.bodyMedium,
              )
              Text(
                text = "Elapsed: " + formatElapsed(running.startedAt),
                modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 10.dp)),
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
                style = MaterialTheme.typography.bodyMedium,
              )
              Button(
                onClick = {
                  if (busy) return@Button
                  busy = true
                  scope.launch {
                    try {
                      api.stopTimesheet(StopTimesheetRequest())
                      refresh()
                    } catch (t: Throwable) {
                      error = t.message ?: "Failed to stop timesheet."
                    } finally {
                      busy = false
                    }
                  }
                },
                enabled = !busy,
                modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
              ) {
                Text(if (busy) "Stopping..." else "Stop")
              }
            }
          }
        }

        item {
          Text(
            text = "Schedule",
            style = MaterialTheme.typography.titleMedium,
            color = MaterialTheme.colorScheme.onBackground,
          )
        }

        val tk = todayKey()
        val todays = events.filter { it.startsAt.startsWith(tk) }
        if (events.isEmpty()) {
          item { Text(text = "No upcoming events.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
        } else {
          item {
            Card(modifier = Modifier.fillMaxWidth()) {
              Text(
                text = if (todays.isEmpty()) "No events today." else "Today's events (${todays.size})",
                modifier = Modifier.padding(PaddingValues(14.dp)),
                color = MaterialTheme.colorScheme.onSurface,
                style = MaterialTheme.typography.titleMedium,
              )
              if (todays.isNotEmpty()) {
                todays.take(5).forEach { ev ->
                  Text(
                    text = (ev.projectName ?: "Project #${ev.projectId}") + " • " + ev.title,
                    modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 6.dp)),
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f),
                    style = MaterialTheme.typography.bodyMedium,
                  )
                  Text(
                    text = ev.startsAt + " → " + ev.endsAt,
                    modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 10.dp)),
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.7f),
                    style = MaterialTheme.typography.bodySmall,
                  )
                }
              }
            }
          }
        }

        item {
          Text(
            text = "Assigned projects",
            style = MaterialTheme.typography.titleMedium,
            color = MaterialTheme.colorScheme.onBackground,
          )
        }

        if (projects.isEmpty()) {
          item { Text(text = "No assigned projects.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f)) }
        }

        items(projects) { p ->
          Card(modifier = Modifier.fillMaxWidth()) {
            Text(
              text = p.name,
              modifier = Modifier.padding(PaddingValues(14.dp)),
              color = MaterialTheme.colorScheme.onSurface,
              style = MaterialTheme.typography.titleMedium,
            )
            Text(
              text = "${p.status}  •  ${p.address}",
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 6.dp)),
              color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.75f),
              style = MaterialTheme.typography.bodyMedium,
            )
            Button(
              onClick = {
                if (busy || running != null) return@Button
                busy = true
                scope.launch {
                  try {
                    api.startTimesheet(StartTimesheetRequest(projectId = p.id))
                    refresh()
                  } catch (t: Throwable) {
                    error = t.message ?: "Failed to start timesheet."
                  } finally {
                    busy = false
                  }
                }
              },
              enabled = !busy && running == null,
              modifier = Modifier.padding(PaddingValues(start = 14.dp, end = 14.dp, bottom = 14.dp)),
            ) {
              Text(if (running != null) "Running" else if (busy) "Starting..." else "Start time")
            }
          }
        }
      }
    }
  }
}
