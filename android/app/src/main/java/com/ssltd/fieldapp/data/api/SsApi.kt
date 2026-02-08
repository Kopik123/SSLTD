package com.ssltd.fieldapp.data.api

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.Query

interface SsApi {
  @POST("api/auth/login")
  suspend fun login(@Body req: LoginRequest): LoginResponse

  @GET("api/projects")
  suspend fun listProjects(
    @Query("status") status: String? = null,
    @Query("assigned") assigned: String? = null,
  ): ProjectsResponse

  @GET("api/projects/{id}")
  suspend fun getProject(@Path("id") id: Int): ProjectResponse

  @GET("api/threads")
  suspend fun listThreads(
    @Query("scope") scope: String,
    @Query("scope_id") scopeId: Int,
  ): ThreadsResponse

  @GET("api/threads/{id}/messages")
  suspend fun getMessages(
    @Path("id") id: Int,
    @Query("after") after: String? = null,
    @Query("limit") limit: Int? = null,
  ): MessagesResponse

  @POST("api/threads/{id}/messages")
  suspend fun sendMessage(
    @Path("id") id: Int,
    @Body req: SendMessageRequest,
  ): SendMessageResponse

  @POST("api/timesheets/start")
  suspend fun startTimesheet(@Body req: StartTimesheetRequest): StartTimesheetResponse

  @POST("api/timesheets/stop")
  suspend fun stopTimesheet(@Body req: StopTimesheetRequest): StopTimesheetResponse

  @GET("api/timesheets")
  suspend fun listTimesheets(
    @Query("from") from: String? = null,
    @Query("to") to: String? = null,
  ): TimesheetsResponse

  @GET("api/schedule")
  suspend fun listSchedule(
    @Query("from") from: String? = null,
    @Query("to") to: String? = null,
    @Query("limit") limit: Int? = null,
  ): ScheduleResponse

  @Multipart
  @POST("api/uploads")
  suspend fun upload(
    @Part("owner_type") ownerType: RequestBody,
    @Part("owner_id") ownerId: RequestBody,
    @Part("stage") stage: RequestBody,
    @Part("client_visible") clientVisible: RequestBody,
    @Part file: MultipartBody.Part,
  ): UploadResponse

  @GET("api/projects/{id}/checklist/current")
  suspend fun getProjectChecklist(@Path("id") id: Int): ProjectChecklistResponse

  @POST("api/checklist-items/{id}")
  suspend fun updateChecklistItemStatus(
    @Path("id") id: Int,
    @Body req: UpdateChecklistItemRequest,
  ): OkResponse

  @GET("api/projects/{id}/reports")
  suspend fun getProjectReports(
    @Path("id") id: Int,
    @Query("limit") limit: Int? = null,
  ): ProjectReportsResponse

  @POST("api/projects/{id}/reports")
  suspend fun createProjectReport(
    @Path("id") id: Int,
    @Body req: CreateProjectReportRequest,
  ): CreateProjectReportResponse

  @GET("api/projects/{id}/issues")
  suspend fun getProjectIssues(
    @Path("id") id: Int,
    @Query("limit") limit: Int? = null,
  ): ProjectIssuesResponse

  @POST("api/projects/{id}/issues")
  suspend fun createIssue(
    @Path("id") id: Int,
    @Body req: CreateIssueRequest,
  ): CreateIssueResponse

  @POST("api/issues/{id}")
  suspend fun updateIssue(
    @Path("id") id: Int,
    @Body req: UpdateIssueRequest,
  ): OkResponse
}
