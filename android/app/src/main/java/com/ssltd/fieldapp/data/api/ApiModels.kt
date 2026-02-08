package com.ssltd.fieldapp.data.api

data class ApiUser(
  val id: Int,
  val role: String,
  val name: String,
  val email: String,
)

data class LoginRequest(
  val email: String,
  val password: String,
)

data class LoginResponse(
  val token: String,
  val user: ApiUser,
)

data class ApiProject(
  val id: Int,
  val status: String,
  val quoteRequestId: Int? = null,
  val clientUserId: Int? = null,
  val clientName: String? = null,
  val clientEmail: String? = null,
  val name: String,
  val address: String,
  val budgetCents: Long = 0,
  val assignedPmUserId: Int? = null,
  val createdAt: String,
  val updatedAt: String,
  val pmName: String? = null,
)

data class ProjectsResponse(
  val items: List<ApiProject>,
)

data class ProjectResponse(
  val item: ApiProject,
)

data class ApiThread(
  val id: Int,
  val scopeType: String,
  val scopeId: Int,
  val createdAt: String,
)

data class ThreadsResponse(
  val items: List<ApiThread>,
)

data class ApiMessage(
  val id: Int,
  val threadId: Int,
  val senderUserId: Int? = null,
  val body: String,
  val createdAt: String,
  val senderName: String? = null,
  val senderRole: String? = null,
)

data class MessagesResponse(
  val items: List<ApiMessage>,
)

data class SendMessageRequest(
  val body: String,
)

data class SendMessageResponse(
  val id: Int,
)

data class StartTimesheetRequest(
  val projectId: Int,
  val notes: String? = null,
)

data class StartTimesheetResponse(
  val id: Int,
  val startedAt: String,
)

data class StopTimesheetRequest(
  val notes: String? = null,
)

data class StopTimesheetResponse(
  val id: Int,
  val stoppedAt: String,
)

data class ApiTimesheet(
  val id: Int,
  val userId: Int,
  val projectId: Int? = null,
  val startedAt: String,
  val stoppedAt: String? = null,
  val notes: String? = null,
)

data class TimesheetsResponse(
  val items: List<ApiTimesheet>,
)

data class ApiScheduleEvent(
  val id: Int,
  val projectId: Int,
  val projectName: String? = null,
  val title: String,
  val startsAt: String,
  val endsAt: String,
  val status: String,
)

data class ScheduleMeta(
  val from: String? = null,
  val to: String? = null,
  val hasMore: Boolean? = null,
)

data class ScheduleResponse(
  val items: List<ApiScheduleEvent>,
  val meta: ScheduleMeta? = null,
)

data class UploadResponse(
  val ids: List<Int>,
  val failed: Int,
)

data class ApiChecklist(
  val id: Int,
  val projectId: Int? = null,
  val quoteRequestId: Int? = null,
  val status: String,
  val title: String? = null,
  val submittedAt: String? = null,
  val decidedAt: String? = null,
)

data class ApiChecklistItem(
  val id: Int,
  val checklistId: Int,
  val position: Int = 0,
  val title: String,
  val pricingMode: String = "fixed",
  val qty: Double = 0.0,
  val unitCostCents: Long = 0,
  val fixedCostCents: Long = 0,
  val status: String = "todo",
  val createdAt: String? = null,
  val updatedAt: String? = null,
)

data class ProjectChecklistResponse(
  val checklist: ApiChecklist? = null,
  val items: List<ApiChecklistItem> = emptyList(),
)

data class UpdateChecklistItemRequest(
  val status: String,
)

data class OkResponse(
  val ok: Boolean = true,
)

data class ApiProjectReport(
  val id: Int,
  val projectId: Int,
  val body: String,
  val createdAt: String,
  val createdByUserId: Int? = null,
  val createdByName: String? = null,
)

data class ProjectReportsResponse(
  val items: List<ApiProjectReport>,
)

data class CreateProjectReportRequest(
  val body: String,
)

data class CreateProjectReportResponse(
  val id: Int,
)

data class ApiIssue(
  val id: Int,
  val projectId: Int,
  val status: String,
  val severity: String,
  val title: String,
  val body: String? = null,
  val createdByUserId: Int? = null,
  val createdByName: String? = null,
  val resolvedAt: String? = null,
  val createdAt: String,
  val updatedAt: String,
)

data class ProjectIssuesResponse(
  val items: List<ApiIssue>,
)

data class CreateIssueRequest(
  val title: String,
  val severity: String? = null,
  val body: String? = null,
)

data class CreateIssueResponse(
  val id: Int,
)

data class UpdateIssueRequest(
  val status: String? = null,
  val severity: String? = null,
  val title: String? = null,
  val body: String? = null,
)
