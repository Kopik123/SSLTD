package com.ssltd.fieldapp.data

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import java.io.File
import java.util.UUID

object PendingFiles {
  private fun pendingDir(context: Context): File {
    val dir = File(context.filesDir, "pending_uploads")
    if (!dir.exists()) dir.mkdirs()
    return dir
  }

  fun newPendingJpegFile(context: Context): File {
    val name = "cap_" + UUID.randomUUID().toString().replace("-", "") + ".jpg"
    return File(pendingDir(context), name)
  }

  data class Copied(
    val file: File,
    val originalName: String,
    val mimeType: String,
  )

  fun copyUriToPending(context: Context, uri: Uri, allowedMimeTypes: Set<String>): Copied {
    val cr = context.contentResolver
    val mime = (cr.getType(uri) ?: "application/octet-stream").lowercase()
    if (mime !in allowedMimeTypes) {
      throw IllegalArgumentException("Unsupported file type: $mime")
    }

    val originalName = queryDisplayName(context, uri) ?: ("upload_" + UUID.randomUUID().toString())
    val ext = when (mime) {
      "image/jpeg" -> ".jpg"
      "image/png" -> ".png"
      "application/pdf" -> ".pdf"
      else -> ""
    }
    val dest = File(pendingDir(context), "pick_" + UUID.randomUUID().toString().replace("-", "") + ext)

    cr.openInputStream(uri).use { input ->
      if (input == null) throw IllegalArgumentException("Cannot read file")
      dest.outputStream().use { out -> input.copyTo(out) }
    }

    return Copied(file = dest, originalName = originalName, mimeType = mime)
  }

  private fun queryDisplayName(context: Context, uri: Uri): String? {
    return try {
      context.contentResolver.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME), null, null, null).use { c ->
        if (c == null) return null
        if (!c.moveToFirst()) return null
        val idx = c.getColumnIndex(OpenableColumns.DISPLAY_NAME)
        if (idx < 0) return null
        c.getString(idx)
      }
    } catch (_: Throwable) {
      null
    }
  }
}

