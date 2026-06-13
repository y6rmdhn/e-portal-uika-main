<?php

namespace Database\Seeders;

use App\Models\SsoIntegrationTemplate;
use Illuminate\Database\Seeder;

class SsoIntegrationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── FRONTEND ──────────────────────────────────────────────────
            [
                'name'         => 'Next.js',
                'category'     => 'frontend',
                'language'     => 'javascript',
                'icon'         => 'nextjs',
                'description'  => 'Integrasi SSO untuk aplikasi Next.js menggunakan iron-session.',
                'dependencies' => 'npm install axios iron-session',
                'order'        => 1,
                'code_snippet' => <<<'CODE'
// pages/login.jsx
import { useEffect } from "react";
import axios from "axios";

export default function LoginPage() {
  useEffect(() => {
    const handleSsoLogin = async () => {
      const urlParams = new URLSearchParams(window.location.search);
      const ssoToken = urlParams.get("token");
      const roleId = urlParams.get("role_id");
      const appModuleId = urlParams.get("appModule_id");

      if (!ssoToken || !roleId || !appModuleId) return;

      // Bersihkan URL agar token tidak terlihat di browser history
      window.history.replaceState({}, document.title, window.location.pathname);

      try {
        // Hit BE aplikasi kamu untuk validasi token ke E-Portal
        const { data } = await axios.get("/api/sso/callback", {
          params: { token: ssoToken, role_id: roleId, appModule_id: appModuleId },
        });

        if (data.status !== 200) throw new Error(data.message);

        // Simpan session
        await axios.post("/api/login", data.data);

        // Redirect sesuai role
        const role = data.data.role?.toLowerCase();
        window.location.href = role === "admin" ? "/admin" : "/dashboard";
      } catch (err) {
        console.error("[SSO Error]", err);
        alert("SSO Login gagal. Silakan login manual.");
      }
    };

    handleSsoLogin();
  }, []);

  return <div>Memproses SSO...</div>;
}
CODE,
            ],
            [
                'name'         => 'React (Vite)',
                'category'     => 'frontend',
                'language'     => 'javascript',
                'icon'         => 'react',
                'description'  => 'Integrasi SSO untuk aplikasi React dengan Vite.',
                'dependencies' => 'npm install axios',
                'order'        => 2,
                'code_snippet' => <<<'CODE'
// src/pages/Login.tsx
import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";

export default function Login() {
  const navigate = useNavigate();

  useEffect(() => {
    const handleSsoLogin = async () => {
      const params = new URLSearchParams(window.location.search);
      const token = params.get("token");
      const roleId = params.get("role_id");
      const appModuleId = params.get("appModule_id");

      if (!token || !roleId || !appModuleId) return;

      window.history.replaceState({}, "", window.location.pathname);

      try {
        const { data } = await axios.get(
          `${import.meta.env.VITE_API_URL}/sso/callback`,
          { params: { token, role_id: roleId, appModule_id: appModuleId } }
        );

        if (data.status !== 200) throw new Error(data.message);

        localStorage.setItem("token", data.data.token);
        localStorage.setItem("user", JSON.stringify(data.data));

        navigate("/dashboard", { replace: true });
      } catch (err) {
        console.error("[SSO Error]", err);
      }
    };

    handleSsoLogin();
  }, []);

  return <div>Memproses SSO...</div>;
}
CODE,
            ],
            [
                'name'         => 'Vue 3',
                'category'     => 'frontend',
                'language'     => 'javascript',
                'icon'         => 'vue',
                'description'  => 'Integrasi SSO untuk aplikasi Vue 3 dengan Composition API.',
                'dependencies' => 'npm install axios',
                'order'        => 3,
                'code_snippet' => <<<'CODE'
<!-- src/views/LoginView.vue -->
<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

const router = useRouter();

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const token = params.get("token");
  const roleId = params.get("role_id");
  const appModuleId = params.get("appModule_id");

  if (!token || !roleId || !appModuleId) return;

  window.history.replaceState({}, "", window.location.pathname);

  try {
    const { data } = await axios.get(
      `${import.meta.env.VITE_API_URL}/sso/callback`,
      { params: { token, role_id: roleId, appModule_id: appModuleId } }
    );

    if (data.status !== 200) throw new Error(data.message);

    localStorage.setItem("token", data.data.token);
    localStorage.setItem("user", JSON.stringify(data.data));

    router.push("/dashboard");
  } catch (err) {
    console.error("[SSO Error]", err);
  }
});
</script>

<template>
  <div>Memproses SSO...</div>
</template>
CODE,
            ],
            [
                'name'         => 'Vanilla JS',
                'category'     => 'frontend',
                'language'     => 'javascript',
                'icon'         => 'javascript',
                'description'  => 'Integrasi SSO tanpa framework menggunakan Vanilla JavaScript.',
                'dependencies' => '// Tidak ada dependency tambahan',
                'order'        => 4,
                'code_snippet' => <<<'CODE'
// login.js
document.addEventListener("DOMContentLoaded", async () => {
  const params = new URLSearchParams(window.location.search);
  const token = params.get("token");
  const roleId = params.get("role_id");
  const appModuleId = params.get("appModule_id");

  if (!token || !roleId || !appModuleId) return;

  window.history.replaceState({}, "", window.location.pathname);

  try {
    const res = await fetch(
      `/api/sso/callback?token=${token}&role_id=${roleId}&appModule_id=${appModuleId}`
    );
    const data = await res.json();

    if (data.status !== 200) throw new Error(data.message);

    localStorage.setItem("token", data.data.token);
    localStorage.setItem("user", JSON.stringify(data.data));

    window.location.href = "/dashboard";
  } catch (err) {
    console.error("[SSO Error]", err);
    alert("SSO Login gagal.");
  }
});
CODE,
            ],

            // ── BACKEND ───────────────────────────────────────────────────
            [
                'name'         => 'Express.js',
                'category'     => 'backend',
                'language'     => 'javascript',
                'icon'         => 'express',
                'description'  => 'Endpoint SSO callback untuk aplikasi Node.js dengan Express.',
                'dependencies' => 'npm install express axios',
                'order'        => 1,
                'code_snippet' => <<<'CODE'
// routes/sso.js
const express = require("express");
const axios = require("axios");
const router = express.Router();

const EPORTAL_URL = process.env.EPORTAL_URL || "http://localhost:8000";
const SSO_CLIENT_ID = process.env.SSO_CLIENT_ID;
const SSO_CLIENT_SECRET = process.env.SSO_CLIENT_SECRET;

router.get("/callback", async (req, res) => {
  const { token, role_id, appModule_id } = req.query;

  if (!token || !role_id || !appModule_id) {
    return res.status(400).json({ status: 400, message: "Parameter tidak lengkap." });
  }

  try {
    // Verifikasi token ke E-Portal
    const { data } = await axios.post(
      `${EPORTAL_URL}/api/sso/introspect`,
      {},
      {
        headers: {
          "X-SSO-Client-ID": SSO_CLIENT_ID,
          "X-SSO-Client-Secret": SSO_CLIENT_SECRET,
          Authorization: `Bearer ${token}`,
        },
      }
    );

    if (data.status !== 200 || !data.valid) {
      return res.status(401).json({ status: 401, message: "Token tidak valid." });
    }

    const eportalUser = data.user;

    // Cari user di DB lokal kamu berdasarkan email
    // const user = await User.findOne({ email: eportalUser.email });

    // Generate token lokal
    // const localToken = jwt.sign({ id: user.id }, process.env.JWT_SECRET);

    return res.json({
      status: 200,
      message: "SSO berhasil.",
      data: {
        // token: localToken,
        user: eportalUser,
      },
    });
  } catch (err) {
    console.error("[SSO Error]", err.message);
    return res.status(500).json({ status: 500, message: "SSO gagal." });
  }
});

module.exports = router;
CODE,
            ],
            [
                'name'         => 'Laravel',
                'category'     => 'backend',
                'language'     => 'php',
                'icon'         => 'laravel',
                'description'  => 'Endpoint SSO callback untuk aplikasi Laravel.',
                'dependencies' => 'composer require guzzlehttp/guzzle',
                'order'        => 2,
                'code_snippet' => <<<'CODE'
<?php
// routes/api.php
Route::get('/sso/callback', [SsoCallbackController::class, 'handle']);

// app/Http/Controllers/SsoCallbackController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SsoCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $token       = $request->query('token');
        $roleId      = $request->query('role_id');
        $appModuleId = $request->query('appModule_id');

        if (!$token || !$roleId || !$appModuleId) {
            return response()->json(['status' => 400, 'message' => 'Parameter tidak lengkap.'], 400);
        }

        // Verifikasi token ke E-Portal
        $response = Http::withHeaders([
            'X-SSO-Client-ID'     => config('sso.client_id'),
            'X-SSO-Client-Secret' => config('sso.client_secret'),
            'Authorization'       => 'Bearer ' . $token,
        ])->post(config('sso.eportal_url') . '/api/sso/introspect');

        if (!$response->successful() || $response->json('status') !== 200) {
            return response()->json(['status' => 401, 'message' => 'Token tidak valid.'], 401);
        }

        $eportalUser = $response->json('user');

        // Cari atau buat user di DB lokal
        // $user = User::firstOrCreate(['email' => $eportalUser['email']], [...]);

        // Generate token lokal (misal pakai JWT)
        // $localToken = JWTAuth::fromUser($user);

        return response()->json([
            'status'  => 200,
            'message' => 'SSO berhasil.',
            'data'    => [
                // 'token' => $localToken,
                'user'  => $eportalUser,
            ],
        ]);
    }
}
CODE,
            ],
            [
                'name'         => 'Django',
                'category'     => 'backend',
                'language'     => 'python',
                'icon'         => 'django',
                'description'  => 'Endpoint SSO callback untuk aplikasi Django.',
                'dependencies' => 'pip install requests djangorestframework',
                'order'        => 3,
                'code_snippet' => <<<'CODE'
# views.py
import requests
from django.conf import settings
from rest_framework.decorators import api_view
from rest_framework.response import Response

@api_view(["GET"])
def sso_callback(request):
    token        = request.query_params.get("token")
    role_id      = request.query_params.get("role_id")
    app_module_id = request.query_params.get("appModule_id")

    if not token or not role_id or not app_module_id:
        return Response({"status": 400, "message": "Parameter tidak lengkap."}, status=400)

    # Verifikasi token ke E-Portal
    try:
        response = requests.post(
            f"{settings.EPORTAL_URL}/api/sso/introspect",
            headers={
                "X-SSO-Client-ID": settings.SSO_CLIENT_ID,
                "X-SSO-Client-Secret": settings.SSO_CLIENT_SECRET,
                "Authorization": f"Bearer {token}",
            },
        )
        data = response.json()
    except Exception as e:
        return Response({"status": 500, "message": str(e)}, status=500)

    if data.get("status") != 200 or not data.get("valid"):
        return Response({"status": 401, "message": "Token tidak valid."}, status=401)

    eportal_user = data.get("user")

    # Cari atau buat user di DB lokal
    # user, _ = User.objects.get_or_create(email=eportal_user["email"])

    return Response({
        "status": 200,
        "message": "SSO berhasil.",
        "data": {"user": eportal_user},
    })

# urls.py
from django.urls import path
from . import views

urlpatterns = [
    path("sso/callback/", views.sso_callback),
]
CODE,
            ],
            [
                'name'         => 'FastAPI',
                'category'     => 'backend',
                'language'     => 'python',
                'icon'         => 'fastapi',
                'description'  => 'Endpoint SSO callback untuk aplikasi FastAPI.',
                'dependencies' => 'pip install fastapi httpx uvicorn',
                'order'        => 4,
                'code_snippet' => <<<'CODE'
# main.py
import httpx
import os
from fastapi import FastAPI, Query, HTTPException

app = FastAPI()

EPORTAL_URL     = os.getenv("EPORTAL_URL", "http://localhost:8000")
SSO_CLIENT_ID     = os.getenv("SSO_CLIENT_ID")
SSO_CLIENT_SECRET = os.getenv("SSO_CLIENT_SECRET")

@app.get("/sso/callback")
async def sso_callback(
    token: str = Query(...),
    role_id: str = Query(...),
    appModule_id: str = Query(...)
):
    async with httpx.AsyncClient() as client:
        response = await client.post(
            f"{EPORTAL_URL}/api/sso/introspect",
            headers={
                "X-SSO-Client-ID": SSO_CLIENT_ID,
                "X-SSO-Client-Secret": SSO_CLIENT_SECRET,
                "Authorization": f"Bearer {token}",
            },
        )

    data = response.json()

    if data.get("status") != 200 or not data.get("valid"):
        raise HTTPException(status_code=401, detail="Token tidak valid.")

    eportal_user = data.get("user")

    # Cari atau buat user di DB lokal
    # user = await User.get_or_create(email=eportal_user["email"])

    return {
        "status": 200,
        "message": "SSO berhasil.",
        "data": {"user": eportal_user},
    }
CODE,
            ],
            [
                'name'         => 'Spring Boot',
                'category'     => 'backend',
                'language'     => 'java',
                'icon'         => 'spring',
                'description'  => 'Endpoint SSO callback untuk aplikasi Spring Boot.',
                'dependencies' => '// Tambahkan di pom.xml:\n// spring-boot-starter-web\n// spring-boot-starter-webflux',
                'order'        => 5,
                'code_snippet' => <<<'CODE'
// SsoCallbackController.java
package com.example.app.controller;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.reactive.function.client.WebClient;
import java.util.Map;

@RestController
@RequestMapping("/sso")
public class SsoCallbackController {

    @Value("${eportal.url}")
    private String eportalUrl;

    @Value("${sso.client-id}")
    private String clientId;

    @Value("${sso.client-secret}")
    private String clientSecret;

    private final WebClient webClient = WebClient.create();

    @GetMapping("/callback")
    public ResponseEntity<?> callback(
        @RequestParam String token,
        @RequestParam String role_id,
        @RequestParam String appModule_id
    ) {
        // Verifikasi token ke E-Portal
        Map<?, ?> response = webClient.post()
            .uri(eportalUrl + "/api/sso/introspect")
            .header("X-SSO-Client-ID", clientId)
            .header("X-SSO-Client-Secret", clientSecret)
            .header("Authorization", "Bearer " + token)
            .retrieve()
            .bodyToMono(Map.class)
            .block();

        if (response == null || !Integer.valueOf(200).equals(response.get("status"))) {
            return ResponseEntity.status(401).body(Map.of("message", "Token tidak valid."));
        }

        Map<?, ?> eportalUser = (Map<?, ?>) response.get("user");

        // Cari atau buat user di DB lokal
        // User user = userRepository.findByEmail((String) eportalUser.get("email"));

        return ResponseEntity.ok(Map.of(
            "status", 200,
            "message", "SSO berhasil.",
            "data", Map.of("user", eportalUser)
        ));
    }
}
CODE,
            ],
            [
                'name'         => 'Golang',
                'category'     => 'backend',
                'language'     => 'go',
                'icon'         => 'go',
                'description'  => 'Endpoint SSO callback untuk aplikasi Golang dengan net/http.',
                'dependencies' => '// Tidak ada dependency tambahan (pakai standard library)',
                'order'        => 6,
                'code_snippet' => <<<'CODE'
// sso_handler.go
package handler

import (
    "encoding/json"
    "fmt"
    "io"
    "net/http"
    "os"
)

func SsoCallback(w http.ResponseWriter, r *http.Request) {
    token        := r.URL.Query().Get("token")
    roleID       := r.URL.Query().Get("role_id")
    appModuleID  := r.URL.Query().Get("appModule_id")

    if token == "" || roleID == "" || appModuleID == "" {
        http.Error(w, `{"status":400,"message":"Parameter tidak lengkap."}`, http.StatusBadRequest)
        return
    }

    eportalURL    := os.Getenv("EPORTAL_URL")
    clientID      := os.Getenv("SSO_CLIENT_ID")
    clientSecret  := os.Getenv("SSO_CLIENT_SECRET")

    // Verifikasi token ke E-Portal
    req, _ := http.NewRequest("POST", fmt.Sprintf("%s/api/sso/introspect", eportalURL), nil)
    req.Header.Set("X-SSO-Client-ID", clientID)
    req.Header.Set("X-SSO-Client-Secret", clientSecret)
    req.Header.Set("Authorization", "Bearer "+token)

    client := &http.Client{}
    resp, err := client.Do(req)
    if err != nil {
        http.Error(w, `{"status":500,"message":"SSO gagal."}`, http.StatusInternalServerError)
        return
    }
    defer resp.Body.Close()

    body, _ := io.ReadAll(resp.Body)
    var data map[string]interface{}
    json.Unmarshal(body, &data)

    status, _ := data["status"].(float64)
    if status != 200 {
        http.Error(w, `{"status":401,"message":"Token tidak valid."}`, http.StatusUnauthorized)
        return
    }

    eportalUser := data["user"]

    // Cari atau buat user di DB lokal
    // user := db.FindByEmail(eportalUser["email"])

    w.Header().Set("Content-Type", "application/json")
    json.NewEncoder(w).Encode(map[string]interface{}{
        "status":  200,
        "message": "SSO berhasil.",
        "data":    map[string]interface{}{"user": eportalUser},
    })
}
CODE,
            ],
        ];

        foreach ($templates as $template) {
            SsoIntegrationTemplate::create($template);
        }
    }
}
