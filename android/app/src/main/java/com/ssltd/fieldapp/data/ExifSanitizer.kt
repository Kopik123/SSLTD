package com.ssltd.fieldapp.data

import androidx.exifinterface.media.ExifInterface
import java.io.File

object ExifSanitizer {
  fun stripGpsFromJpeg(file: File): Boolean {
    return try {
      val exif = ExifInterface(file)
      val tags = arrayOf(
        ExifInterface.TAG_GPS_LATITUDE,
        ExifInterface.TAG_GPS_LATITUDE_REF,
        ExifInterface.TAG_GPS_LONGITUDE,
        ExifInterface.TAG_GPS_LONGITUDE_REF,
        ExifInterface.TAG_GPS_ALTITUDE,
        ExifInterface.TAG_GPS_ALTITUDE_REF,
        ExifInterface.TAG_GPS_TIMESTAMP,
        ExifInterface.TAG_GPS_DATESTAMP,
        ExifInterface.TAG_GPS_PROCESSING_METHOD,
      )
      for (t in tags) exif.setAttribute(t, null)
      exif.saveAttributes()
      true
    } catch (_: Throwable) {
      false
    }
  }
}

