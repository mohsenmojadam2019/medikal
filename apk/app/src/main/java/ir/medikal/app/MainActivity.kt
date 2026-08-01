package ir.medikal.app

import android.app.Application
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.viewModels
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import ir.medikal.app.data.ApiClient
import ir.medikal.app.data.SessionStore
import ir.medikal.app.ui.theme.MedikalTheme
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.time.LocalDate

enum class Screen { HOME, DOCTORS, APPOINTMENTS, PHARMACY, AI, MORE, LOGIN, BOOKING, EMERGENCY, GENERIC }
data class UiState(
    val screen: Screen = Screen.HOME,
    val loading: Boolean = false,
    val message: String = "",
    val loggedIn: Boolean = false,
    val doctors: List<JSONObject> = emptyList(),
    val specialties: List<JSONObject> = emptyList(),
    val items: List<JSONObject> = emptyList(),
    val selectedDoctor: JSONObject? = null,
    val genericTitle: String = "",
    val aiMessages: List<Pair<Boolean, String>> = listOf(false to "سلام، من دستیار سلامت مدیکال هستم. پرسش پزشکی خود را مطرح کنید."),
    val userName: String = "کاربر مدیکال"
)

class MainViewModel(app: Application) : AndroidViewModel(app) {
    val session = SessionStore(app)
    private val api = ApiClient(session)
    private val _state = MutableStateFlow(UiState(loggedIn = session.isLoggedIn))
    val state = _state.asStateFlow()

    init { loadHome() }
    fun navigate(screen: Screen) { _state.value = _state.value.copy(screen = screen, message = "") }
    fun clearMessage() { _state.value = _state.value.copy(message = "") }
    private fun busy(value: Boolean) { _state.value = _state.value.copy(loading = value) }
    private fun notify(text: String) { _state.value = _state.value.copy(message = text) }

    fun loadHome() = viewModelScope.launch {
        busy(true)
        val doctors = async { api.get("doctors", mapOf("per_page" to "10")) }
        val specs = async { api.get("specialties") }
        _state.value = _state.value.copy(
            loading = false,
            doctors = ApiClient.objects(doctors.await().data),
            specialties = ApiClient.objects(specs.await().data)
        )
    }

    fun loginEmail(email: String, password: String) = viewModelScope.launch {
        busy(true)
        val r = api.post("auth/login/email", JSONObject().put("email", email).put("password", password))
        finishLogin(r.data, r.ok, r.message)
    }

    fun sendOtp(mobile: String, done: () -> Unit) = viewModelScope.launch {
        busy(true)
        val r = api.post("auth/login/mobile", JSONObject().put("mobile", mobile))
        busy(false); if (r.ok) done() else notify(r.message.ifBlank { "ارسال کد ناموفق بود" })
    }

    fun verifyOtp(mobile: String, code: String) = viewModelScope.launch {
        busy(true)
        val r = api.post("auth/login/mobile/verify", JSONObject().put("mobile", mobile).put("code", code).put("otp", code))
        finishLogin(r.data, r.ok, r.message)
    }

    private fun finishLogin(data: Any?, ok: Boolean, error: String) {
        if (!ok) { busy(false); notify(error.ifBlank { "ورود ناموفق بود" }); return }
        val obj = data as? JSONObject
        val token = obj?.optString("token").takeUnless { it.isNullOrBlank() }
            ?: obj?.optString("access_token").orEmpty()
        if (token.isBlank()) { busy(false); notify("توکن ورود در پاسخ سرور یافت نشد"); return }
        session.token = token
        _state.value = _state.value.copy(loading = false, loggedIn = true, screen = Screen.HOME, message = "خوش آمدید")
        loadProfile()
    }

    private fun loadProfile() = viewModelScope.launch {
        val r = api.get("auth/me")
        val root = r.data as? JSONObject
        val user = root?.optJSONObject("user") ?: root
        val name = user?.optString("name").orEmpty().ifBlank { user?.optString("full_name").orEmpty() }
        if (name.isNotBlank()) _state.value = _state.value.copy(userName = name)
    }

    fun logout() { session.clear(); _state.value = UiState(screen = Screen.LOGIN, loggedIn = false) }
    fun saveApiUrl(url: String) { session.apiUrl = url.trim().trimEnd('/') + "/"; notify("آدرس سرور ذخیره شد") }

    fun openDoctors() { navigate(Screen.DOCTORS); if (_state.value.doctors.isEmpty()) loadHome() }
    fun selectDoctor(doctor: JSONObject) { _state.value = _state.value.copy(screen = Screen.BOOKING, selectedDoctor = doctor) }
    fun book(date: String, time: String, notes: String) = protectedAction {
        val id = _state.value.selectedDoctor?.optInt("id") ?: 0
        api.post("appointments", JSONObject().put("doctor_id", id).put("date", date).put("start_time", time).put("notes", notes))
    }

    fun openAppointments() = loadGeneric("نوبت‌های من", "appointments/my/appointments", Screen.APPOINTMENTS)
    fun openPharmacy() = loadGeneric("داروخانه آنلاین", "pharmacy/pharmacies", Screen.PHARMACY)
    fun openModule(title: String, path: String) = loadGeneric(title, path, Screen.GENERIC)

    private fun loadGeneric(title: String, path: String, screen: Screen) = viewModelScope.launch {
        if (!session.isLoggedIn && path.contains("my")) { navigate(Screen.LOGIN); return@launch }
        _state.value = _state.value.copy(screen = screen, genericTitle = title, loading = true, items = emptyList())
        val r = api.get(path)
        _state.value = _state.value.copy(loading = false, items = ApiClient.objects(r.data), message = if (r.ok) "" else r.message)
    }

    private fun protectedAction(block: suspend () -> ir.medikal.app.data.ApiResult) = viewModelScope.launch {
        if (!session.isLoggedIn) { navigate(Screen.LOGIN); return@launch }
        busy(true); val r = block(); busy(false)
        notify(if (r.ok) r.message.ifBlank { "عملیات با موفقیت انجام شد" } else r.message.ifBlank { "عملیات ناموفق بود" })
        if (r.ok) navigate(Screen.HOME)
    }

    fun sendAi(question: String) = viewModelScope.launch {
        if (!session.isLoggedIn) { navigate(Screen.LOGIN); return@launch }
        _state.value = _state.value.copy(loading = true, aiMessages = _state.value.aiMessages + (true to question))
        val r = api.post("v1/chat/medical/ask", JSONObject().put("question", question))
        val answer = (r.data as? JSONObject)?.optString("response").orEmpty()
            .ifBlank { r.message.ifBlank { "پاسخی از سرور دریافت نشد." } }
        _state.value = _state.value.copy(loading = false, aiMessages = _state.value.aiMessages + (false to answer))
    }

    fun emergency(name: String, mobile: String, address: String, complaint: String) = protectedAction {
        api.post("emergency/request", JSONObject().put("patient_name", name).put("mobile", mobile)
            .put("address", address).put("chief_complaint", complaint))
    }
}

class MainActivity : ComponentActivity() {
    private val vm by viewModels<MainViewModel>()
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { MedikalTheme { CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) { MedikalApp(vm) } } }
    }
}

@Composable
fun MedikalApp(vm: MainViewModel) {
    val state by vm.state.collectAsState()
    val snackbar = remember { SnackbarHostState() }
    LaunchedEffect(state.message) { if (state.message.isNotBlank()) { snackbar.showSnackbar(state.message); vm.clearMessage() } }
    Scaffold(
        snackbarHost = { SnackbarHost(snackbar) },
        bottomBar = { if (state.screen !in listOf(Screen.LOGIN, Screen.BOOKING, Screen.EMERGENCY)) BottomNav(state.screen, vm) }
    ) { padding ->
        Box(Modifier.fillMaxSize().padding(padding)) {
            when (state.screen) {
                Screen.HOME -> HomeScreen(state, vm)
                Screen.DOCTORS -> DoctorsScreen(state, vm)
                Screen.LOGIN -> LoginScreen(state, vm)
                Screen.BOOKING -> BookingScreen(state, vm)
                Screen.APPOINTMENTS, Screen.PHARMACY, Screen.GENERIC -> ListScreen(state, vm)
                Screen.AI -> AiScreen(state, vm)
                Screen.EMERGENCY -> EmergencyScreen(state, vm)
                Screen.MORE -> MoreScreen(state, vm)
            }
            if (state.loading) Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
        }
    }
}

@Composable
private fun BottomNav(screen: Screen, vm: MainViewModel) {
    NavigationBar(containerColor = Color.White, tonalElevation = 2.dp) {
        listOf(
            Triple(Screen.HOME, "خانه", Icons.Outlined.Home),
            Triple(Screen.DOCTORS, "پزشکان", Icons.Outlined.MedicalServices),
            Triple(Screen.AI, "هوش مصنوعی", Icons.Outlined.AutoAwesome),
            Triple(Screen.PHARMACY, "داروخانه", Icons.Outlined.LocalPharmacy),
            Triple(Screen.MORE, "بیشتر", Icons.Outlined.GridView)
        ).forEach { (target, label, icon) ->
            NavigationBarItem(selected = screen == target, onClick = {
                when (target) { Screen.DOCTORS -> vm.openDoctors(); Screen.PHARMACY -> vm.openPharmacy(); else -> vm.navigate(target) }
            }, icon = { Icon(icon, label) }, label = { Text(label) })
        }
    }
}

@Composable
private fun HomeScreen(state: UiState, vm: MainViewModel) {
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(20.dp)) {
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column { Text("سلام، ${state.userName}", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black); Text("امروز حالتان چطور است؟", color = Color.Gray) }
                IconButton(onClick = { vm.openModule("اعلان‌ها", "notifications") }) { Icon(Icons.Outlined.Notifications, "اعلان‌ها") }
            }
        }
        item { HeroCard { vm.navigate(Screen.AI) } }
        item { Text("خدمات سریع", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold) }
        item {
            val actions: List<Triple<String, androidx.compose.ui.graphics.vector.ImageVector, () -> Unit>> = listOf(
                Triple("نوبت پزشک", Icons.Outlined.CalendarMonth) { vm.openDoctors() },
                Triple("پزشک در خانه", Icons.Outlined.HomeWork) { vm.navigate(Screen.EMERGENCY) },
                Triple("داروخانه", Icons.Outlined.LocalPharmacy) { vm.openPharmacy() },
                Triple("آزمایشگاه", Icons.Outlined.Biotech) { vm.openModule("آزمایشگاه", "lab/tests/active") },
                Triple("نسخه‌ها", Icons.Outlined.ReceiptLong) { vm.openModule("نسخه‌های من", "prescriptions/my") },
                Triple("پرونده پزشکی", Icons.Outlined.FolderShared) { vm.openModule("پرونده پزشکی", "medical-notes") }
            )
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) { actions.chunked(2).forEach { row -> Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) { row.forEach { (t, i, a) -> ActionCard(t, i, a, Modifier.weight(1f)) }; if (row.size == 1) Spacer(Modifier.weight(1f)) } } }
        }
        item { Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) { Text("پزشکان پیشنهادی", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold); TextButton(onClick = vm::openDoctors) { Text("مشاهده همه") } } }
        item { LazyRow(horizontalArrangement = Arrangement.spacedBy(12.dp)) { items(state.doctors.take(6)) { DoctorMini(it) { vm.selectDoctor(it) } } } }
        item { Spacer(Modifier.height(20.dp)) }
    }
}

@Composable
private fun HeroCard(onClick: () -> Unit) {
    Card(Modifier.fillMaxWidth().clickable(onClick = onClick), shape = RoundedCornerShape(28.dp), colors = CardDefaults.cardColors(containerColor = Color.White)) {
        Row(Modifier.padding(24.dp), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) { Text("دستیار هوشمند سلامت", color = Color.Black, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black); Spacer(Modifier.height(8.dp)); Text("علائم خود را بگویید و راهنمایی اولیه دریافت کنید", color = Color.DarkGray); Spacer(Modifier.height(14.dp)); Button(onClick = onClick, colors = ButtonDefaults.buttonColors(containerColor = Color.Black)) { Text("شروع گفتگو") } }
            Icon(Icons.Outlined.HealthAndSafety, null, tint = Color.Black, modifier = Modifier.size(70.dp))
        }
    }
}

@Composable
private fun ActionCard(title: String, icon: androidx.compose.ui.graphics.vector.ImageVector, click: () -> Unit, modifier: Modifier = Modifier) {
    OutlinedCard(modifier.clickable(onClick = click), border = BorderStroke(1.dp, Color(0xFFD0D0D0)), shape = RoundedCornerShape(20.dp)) {
        Row(Modifier.fillMaxWidth().padding(16.dp), verticalAlignment = Alignment.CenterVertically) { Surface(shape = RoundedCornerShape(14.dp), color = Color.White) { Icon(icon, null, tint = Color.Black, modifier = Modifier.padding(10.dp)) }; Spacer(Modifier.width(12.dp)); Text(title, fontWeight = FontWeight.Bold) }
    }
}

@Composable
private fun DoctorMini(obj: JSONObject, click: () -> Unit) {
    Card(Modifier.width(230.dp).clickable(onClick = click), shape = RoundedCornerShape(22.dp)) { Column(Modifier.padding(18.dp)) { Icon(Icons.Outlined.AccountCircle, null, Modifier.size(48.dp)); Text(displayName(obj, "پزشک"), fontWeight = FontWeight.Bold); Text(nestedText(obj, "specialty", "name").ifBlank { "پزشک عمومی" }, color = Color.Gray); Spacer(Modifier.height(8.dp)); Text("مشاهده و رزرو ←") } }
}

@Composable
private fun DoctorsScreen(state: UiState, vm: MainViewModel) {
    var query by remember { mutableStateOf("") }
    val filtered = state.doctors.filter { displayName(it, "").contains(query, true) || it.toString().contains(query, true) }
    LazyColumn(contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item { PageHeader("پزشکان متخصص", "انتخاب سریع و رزرو آنلاین") }
        item { OutlinedTextField(query, { query = it }, Modifier.fillMaxWidth(), placeholder = { Text("جستجوی نام یا تخصص") }, leadingIcon = { Icon(Icons.Outlined.Search, null) }, singleLine = true, shape = RoundedCornerShape(18.dp)) }
        items(filtered) { doctor -> OutlinedCard(Modifier.fillMaxWidth().clickable { vm.selectDoctor(doctor) }, shape = RoundedCornerShape(22.dp)) { Row(Modifier.padding(18.dp), verticalAlignment = Alignment.CenterVertically) { Icon(Icons.Outlined.AccountCircle, null, Modifier.size(56.dp)); Spacer(Modifier.width(14.dp)); Column(Modifier.weight(1f)) { Text(displayName(doctor, "پزشک"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium); Text(nestedText(doctor, "specialty", "name").ifBlank { doctor.optString("specialty_name", "پزشک عمومی") }, color = Color.Gray); Text("هزینه: ${doctor.optString("consultation_fee", "—")} تومان") }; Icon(Icons.Outlined.ChevronLeft, null) } } }
        if (filtered.isEmpty() && !state.loading) item { Empty("پزشکی یافت نشد") }
    }
}

@Composable
private fun BookingScreen(state: UiState, vm: MainViewModel) {
    val doctor = state.selectedDoctor ?: return
    var date by remember { mutableStateOf(LocalDate.now().plusDays(1).toString()) }
    var time by remember { mutableStateOf("09:00") }
    var notes by remember { mutableStateOf("") }
    FormPage("رزرو نوبت", onBack = { vm.openDoctors() }) {
        Text(displayName(doctor, "پزشک"), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black)
        Text(nestedText(doctor, "specialty", "name").ifBlank { "مشاوره پزشکی" }, color = Color.Gray)
        Field("تاریخ میلادی (YYYY-MM-DD)", date) { date = it }
        Field("ساعت (HH:MM)", time) { time = it }
        Field("توضیحات برای پزشک", notes, 4) { notes = it }
        Button({ vm.book(date, time, notes) }, Modifier.fillMaxWidth().height(54.dp)) { Text("ثبت و ادامه پرداخت") }
    }
}

@Composable
private fun LoginScreen(state: UiState, vm: MainViewModel) {
    var emailMode by remember { mutableStateOf(false) }; var otpSent by remember { mutableStateOf(false) }
    var mobile by remember { mutableStateOf("") }; var otp by remember { mutableStateOf("") }; var email by remember { mutableStateOf("") }; var pass by remember { mutableStateOf("") }
    FormPage("ورود به مدیکال", onBack = { vm.navigate(Screen.HOME) }) {
        Text("برای استفاده از خدمات شخصی وارد شوید", color = Color.Gray)
        if (emailMode) {
            Field("ایمیل", email) { email = it }
            OutlinedTextField(pass, { pass = it }, Modifier.fillMaxWidth(), label = { Text("رمز عبور") }, visualTransformation = PasswordVisualTransformation(), singleLine = true)
            Button({ vm.loginEmail(email, pass) }, Modifier.fillMaxWidth()) { Text("ورود با ایمیل") }
        } else if (!otpSent) {
            OutlinedTextField(mobile, { mobile = it }, Modifier.fillMaxWidth(), label = { Text("شماره موبایل") }, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), singleLine = true)
            Button({ vm.sendOtp(mobile) { otpSent = true } }, Modifier.fillMaxWidth()) { Text("دریافت کد یک‌بارمصرف") }
        } else {
            Field("کد تأیید", otp) { otp = it }
            Button({ vm.verifyOtp(mobile, otp) }, Modifier.fillMaxWidth()) { Text("تأیید و ورود") }
        }
        TextButton({ emailMode = !emailMode; otpSent = false }) { Text(if (emailMode) "ورود با شماره موبایل" else "ورود با ایمیل") }
    }
}

@Composable
private fun ListScreen(state: UiState, vm: MainViewModel) {
    LazyColumn(contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item { PageHeader(state.genericTitle.ifBlank { if (state.screen == Screen.PHARMACY) "داروخانه آنلاین" else "نوبت‌های من" }, "اطلاعات همگام با سامانه مدیکال") }
        items(state.items) { obj -> GenericRow(obj) }
        if (state.items.isEmpty() && !state.loading) item { Empty("موردی برای نمایش وجود ندارد") }
    }
}

@Composable
private fun GenericRow(obj: JSONObject) {
    val title = displayName(obj, obj.optString("title").ifBlank { obj.optString("code", "جزئیات خدمت") })
    val sub = listOf("specialty_name", "status_label", "status", "address", "description", "date").firstNotNullOfOrNull { obj.optString(it).takeIf(String::isNotBlank) }.orEmpty()
    OutlinedCard(Modifier.fillMaxWidth(), shape = RoundedCornerShape(20.dp)) { Row(Modifier.padding(18.dp), verticalAlignment = Alignment.CenterVertically) { Icon(Icons.Outlined.HealthAndSafety, null); Spacer(Modifier.width(14.dp)); Column(Modifier.weight(1f)) { Text(title, fontWeight = FontWeight.Bold); if (sub.isNotBlank()) Text(sub, color = Color.Gray, maxLines = 2) }; Icon(Icons.Outlined.ChevronLeft, null) } }
}

@Composable
private fun AiScreen(state: UiState, vm: MainViewModel) {
    var text by remember { mutableStateOf("") }
    Column(Modifier.fillMaxSize().padding(16.dp)) {
        PageHeader("دستیار هوشمند پزشکی", "این سرویس جایگزین تشخیص پزشک نیست")
        LazyColumn(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(10.dp), contentPadding = PaddingValues(vertical = 16.dp)) {
            items(state.aiMessages) { (mine, msg) -> Row(Modifier.fillMaxWidth(), horizontalArrangement = if (mine) Arrangement.Start else Arrangement.End) { Surface(color = if (mine) Color(0xFF202020) else Color.White, contentColor = if (mine) Color.White else Color.Black, shadowElevation = 1.dp, shape = RoundedCornerShape(18.dp), modifier = Modifier.widthIn(max = 310.dp)) { Text(msg, Modifier.padding(14.dp)) } } }
        }
        Row(verticalAlignment = Alignment.CenterVertically) { OutlinedTextField(text, { text = it }, Modifier.weight(1f), placeholder = { Text("سؤال پزشکی خود را بنویسید") }, maxLines = 4, shape = RoundedCornerShape(20.dp)); Spacer(Modifier.width(8.dp)); FilledIconButton(onClick = { if (text.isNotBlank()) { vm.sendAi(text); text = "" } }) { Icon(Icons.Outlined.Send, "ارسال") } }
    }
}

@Composable
private fun EmergencyScreen(state: UiState, vm: MainViewModel) {
    var name by remember { mutableStateOf("") }; var mobile by remember { mutableStateOf("") }; var address by remember { mutableStateOf("") }; var complaint by remember { mutableStateOf("") }
    FormPage("پزشک در خانه و اورژانس", onBack = { vm.navigate(Screen.HOME) }) {
        Surface(color = Color(0xFFFFE9E7), shape = RoundedCornerShape(18.dp)) { Text("در وضعیت تهدیدکننده حیات فوراً با ۱۱۵ تماس بگیرید.", Modifier.padding(16.dp), color = Color(0xFF8C1D18)) }
        Field("نام بیمار", name) { name = it }; Field("موبایل", mobile) { mobile = it }; Field("آدرس دقیق", address, 3) { address = it }; Field("شرح مشکل یا علائم", complaint, 4) { complaint = it }
        Button({ vm.emergency(name, mobile, address, complaint) }, Modifier.fillMaxWidth().height(54.dp)) { Text("ثبت درخواست اعزام") }
    }
}

@Composable
private fun MoreScreen(state: UiState, vm: MainViewModel) {
    var url by remember { mutableStateOf(vm.session.apiUrl) }
    LazyColumn(contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        item { PageHeader("خدمات و حساب کاربری", if (state.loggedIn) state.userName else "برای خدمات شخصی وارد شوید") }
        val modules = listOf("نوبت‌های من" to "appointments/my/appointments", "کیف پول" to "wallet/summary", "پرداخت‌ها" to "payments/history", "نسخه‌ها" to "prescriptions/my", "آزمایشگاه" to "lab/tests/active", "گفتگوها" to "chat/conversations", "اعلان‌ها" to "notifications", "پرونده پزشکی" to "medical-notes")
        items(modules) { (title, path) -> ActionCard(title, Icons.Outlined.ArrowBack, { vm.openModule(title, path) }, Modifier.fillMaxWidth()) }
        item { HorizontalDivider(); Text("تنظیمات اتصال", fontWeight = FontWeight.Bold); Field("آدرس پایه API", url) { url = it }; OutlinedButton({ vm.saveApiUrl(url) }, Modifier.fillMaxWidth()) { Text("ذخیره آدرس سرور") } }
        item { if (state.loggedIn) Button(vm::logout, Modifier.fillMaxWidth(), colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = Color.Black)) { Text("خروج از حساب") } else Button({ vm.navigate(Screen.LOGIN) }, Modifier.fillMaxWidth()) { Text("ورود / ثبت‌نام") } }
        item { Text("نسخه ۱.۰.۰ • طراحی بومی RTL", color = Color.Gray) }
    }
}

@Composable private fun PageHeader(title: String, subtitle: String) { Column { Text(title, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Black); Text(subtitle, color = Color.Gray) } }
@Composable private fun Empty(text: String) { Box(Modifier.fillMaxWidth().padding(48.dp), contentAlignment = Alignment.Center) { Text(text, color = Color.Gray) } }
@Composable private fun Field(label: String, value: String, lines: Int = 1, change: (String) -> Unit) { OutlinedTextField(value, change, Modifier.fillMaxWidth(), label = { Text(label) }, minLines = lines, maxLines = lines.coerceAtLeast(4), singleLine = lines == 1, shape = RoundedCornerShape(16.dp)) }
@Composable private fun FormPage(title: String, onBack: () -> Unit, content: @Composable ColumnScope.() -> Unit) { LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) { item { Row(verticalAlignment = Alignment.CenterVertically) { IconButton(onClick = onBack) { Icon(Icons.Outlined.ArrowForward, "بازگشت") }; Text(title, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black) } }; item { Column(verticalArrangement = Arrangement.spacedBy(14.dp), content = content) } } }
private fun displayName(o: JSONObject, fallback: String): String = o.optString("full_name").ifBlank { o.optString("name") }.ifBlank { o.optJSONObject("user")?.optString("name").orEmpty() }.ifBlank { fallback }
private fun nestedText(o: JSONObject, parent: String, key: String): String = o.optJSONObject(parent)?.optString(key).orEmpty()
