package ir.medikal.app.data

import org.json.JSONArray
import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Test

class ApiClientTest {
    @Test fun extractsLaravelPaginatorData() {
        val payload = JSONObject().put("data", JSONArray().put(JSONObject().put("id", 7)))
        val objects = ApiClient.objects(payload)
        assertEquals(1, objects.size)
        assertEquals(7, objects.first().getInt("id"))
    }

    @Test fun extractsNamedDoctorArray() {
        val payload = JSONObject().put("doctors", JSONArray().put(JSONObject().put("name", "پزشک")))
        assertEquals("پزشک", ApiClient.objects(payload).first().getString("name"))
    }
}
