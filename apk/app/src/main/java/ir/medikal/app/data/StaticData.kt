package ir.medikal.app.data

import org.json.JSONObject

object StaticData {
    val specialties = listOf("قلب و عروق", "داخلی", "اطفال", "زنان", "پوست و مو", "ارتوپدی", "روان‌شناسی", "گوش و حلق و بینی")
        .mapIndexed { index, name -> JSONObject().put("id", index + 1).put("name", name).put("is_active", true) }

    val doctors = listOf(
        arrayOf("دکتر سارا احمدی", "قلب و عروق", "650000", "4.9", "۱۲ سال سابقه"),
        arrayOf("دکتر امیر رضایی", "داخلی", "480000", "4.8", "۹ سال سابقه"),
        arrayOf("دکتر نازنین محمدی", "اطفال", "520000", "4.9", "۱۱ سال سابقه"),
        arrayOf("دکتر علی اکبری", "ارتوپدی", "590000", "4.7", "۱۵ سال سابقه"),
        arrayOf("دکتر مریم کریمی", "پوست و مو", "550000", "4.8", "۸ سال سابقه"),
        arrayOf("دکتر نیما شریفی", "گوش و حلق و بینی", "500000", "4.6", "۱۰ سال سابقه")
    ).mapIndexed { index, row -> JSONObject()
        .put("id", index + 1).put("full_name", row[0])
        .put("specialty", JSONObject().put("name", row[1]))
        .put("consultation_fee", row[2]).put("rating", row[3]).put("experience", row[4])
        .put("is_available", true)
    }

    val pharmacies = listOf(
        arrayOf("داروخانه شبانه‌روزی سلامت", "تهران، میدان ونک", "باز • ارسال ۳۰ دقیقه‌ای"),
        arrayOf("داروخانه دکتر نوروزی", "تهران، خیابان ولیعصر", "باز • طرف قرارداد بیمه"),
        arrayOf("داروخانه آنلاین مدیکال", "ارسال سراسری", "۲۴ ساعته • ارسال رایگان")
    ).mapIndexed { i, row -> JSONObject().put("id", i + 1).put("name", row[0]).put("address", row[1]).put("description", row[2]) }

    val products = listOf(
        arrayOf("استامینوفن ۵۰۰", "مسکن و تب‌بر", "48000"),
        arrayOf("ویتامین D3", "مکمل غذایی", "125000"),
        arrayOf("سرم شست‌وشو", "بهداشت و مراقبت", "76000"),
        arrayOf("فشارسنج دیجیتال", "تجهیزات پزشکی", "1850000")
    ).mapIndexed { i, row -> JSONObject().put("id", i + 1).put("name", row[0]).put("description", row[1]).put("price", row[2]).put("in_stock", true) }

    val labTests = listOf("آزمایش کامل خون (CBC)", "قند خون ناشتا", "تیروئید", "ویتامین D", "چربی خون", "آزمایش عملکرد کبد")
        .mapIndexed { i, name -> JSONObject().put("id", i + 1).put("name", name).put("description", "نمونه‌گیری در منزل یا آزمایشگاه").put("price", (180000 + i * 55000).toString()) }

    val appointments = mutableListOf(
        JSONObject().put("id", 1).put("name", "ویزیت دکتر سارا احمدی").put("date", "۱۴۰۵/۰۵/۱۸ • ساعت ۱۰:۳۰").put("status_label", "تأیید شده"),
        JSONObject().put("id", 2).put("name", "مشاوره آنلاین دکتر امیر رضایی").put("date", "۱۴۰۵/۰۵/۲۲ • ساعت ۱۷:۰۰").put("status_label", "در انتظار")
    )

    val prescriptions = listOf(
        JSONObject().put("id", 1).put("name", "نسخه دکتر امیر رضایی").put("date", "۱۴۰۵/۰۴/۲۱").put("status_label", "قابل سفارش"),
        JSONObject().put("id", 2).put("name", "نسخه دکتر سارا احمدی").put("date", "۱۴۰۵/۰۳/۱۰").put("status_label", "تحویل شده")
    )

    fun forPath(path: String): List<JSONObject> = when {
        path.startsWith("doctors") -> doctors
        path.startsWith("specialties") -> specialties
        path.contains("appointments") -> appointments
        path.contains("pharmacy/pharmacies") -> pharmacies
        path.contains("products") -> products
        path.contains("lab/") -> labTests
        path.contains("prescriptions") -> prescriptions
        path.contains("wallet") -> listOf(JSONObject().put("name", "موجودی کیف پول").put("description", "۲,۵۰۰,۰۰۰ تومان"), JSONObject().put("name", "پرداخت ویزیت").put("date", "امروز").put("status_label", "۴۸۰,۰۰۰- تومان"))
        path.contains("payments") -> listOf(JSONObject().put("name", "پرداخت نوبت پزشکی").put("date", "۱۴۰۵/۰۵/۰۱").put("status_label", "موفق"))
        path.contains("notifications") -> listOf(JSONObject().put("name", "یادآوری نوبت").put("description", "نوبت شما فردا ساعت ۱۰:۳۰ است"), JSONObject().put("name", "نسخه آماده است").put("description", "نسخه شما قابل سفارش از داروخانه است"))
        path.contains("chat") -> listOf(JSONObject().put("name", "پشتیبانی مدیکال").put("description", "چطور می‌توانیم کمک کنیم؟"))
        path.contains("medical-notes") -> listOf(JSONObject().put("name", "پرونده سلامت عمومی").put("description", "گروه خونی O+ • بدون حساسیت ثبت‌شده"))
        else -> emptyList()
    }

    fun aiAnswer(question: String): String = when {
        listOf("قفسه", "قلب", "نفس").any { question.contains(it) } -> "اگر درد شدید قفسه سینه، تنگی نفس یا تعریق سرد دارید فوراً با ۱۱۵ تماس بگیرید. در علائم خفیف نیز ارزیابی پزشک ضروری است."
        listOf("تب", "سرما", "گلو").any { question.contains(it) } -> "استراحت، مصرف مایعات و اندازه‌گیری منظم دما کمک‌کننده است. اگر تب بیش از سه روز، تنگی نفس یا کاهش هوشیاری داشتید به پزشک مراجعه کنید."
        listOf("سردرد", "سر").any { question.contains(it) } -> "آب کافی، استراحت در محیط آرام و بررسی فشار خون پیشنهاد می‌شود. سردرد ناگهانی و بسیار شدید یا همراه ضعف اندام نیازمند اورژانس است."
        else -> "اطلاعات شما ثبت شد. برای راهنمایی ایمن‌تر، مدت شروع علائم، شدت، سن و بیماری‌های زمینه‌ای را هم بنویسید. این پاسخ تشخیص پزشکی قطعی نیست."
    }
}
