<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import AdminUserManagement from './components/AdminUserManagement.vue'

const username = ref('')
const password = ref('')
const accessToken = ref('')
const loginMode = ref('staff')
const message = ref('')
const isLoading = ref(false)
const user = ref(null)
const stats = ref(null)
const studentDashboard = ref(null)
const guruDashboard = ref(null)
const guruStudents = ref([])
const selectedStudentDetail = ref(null)
const selectedStudentId = ref(null)
const selectedTab = ref('basic')
const studentSearch = ref('')
const guruMaterials = ref([])
const materialForm = ref({ title: '', category_id: '', type: 'pdf', file: null, youtube_url: '', description: '' })
const materialMessage = ref('')
const materialError = ref(false)
const isUploadingMaterial = ref(false)
const guruView = ref('home')
const guruProgress = ref(null)
const guruQuizzes = ref([])
const quizForm = ref({ material_id: '', title: '', description: '', passing_score: 70, questions: [{ question_text: '', answers: [{ answer_text: '', is_correct: true }, { answer_text: '', is_correct: false }] }] })
const quizMessage = ref('')
const quizError = ref(false)
const isSavingQuiz = ref(false)
const studentQuizzes = ref([])
const activeQuiz = ref(null)
const quizAnswers = ref({})
const quizResult = ref(null)
const assistantQuestion = ref('')
const assistantMessages = ref([{ role: 'assistant', text: 'Hai! Aku MathBot 🤖✨ Aku siap membantu menjelaskan materi belajar dengan cara sederhana.' }])
const assistantDifficulty = ref('medium')
const assistantLoading = ref(false)
const assistantError = ref('')
const studentLearning = ref([])
const studentView = ref('home')
const selectedLearning = ref(null)
const selectedMaterial = ref(null)
const currentView = ref(window.location.pathname.includes('/admin/tokens') ? 'tokens' : window.location.pathname.includes('/admin/users') ? 'users' : 'dashboard')
const tokenForm = ref({
  nama: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  gender: '',
  disability: '',
  nama_orang_tua_wali: '',
  pendamping_email: '',
  pendamping_phone: '',
})
const tokenMessage = ref('')
const tokenError = ref(false)
const generatedToken = ref('')
const isGeneratingToken = ref(false)

const isPendamping = computed(() => user.value?.sessionType === 'pendamping')
const isGuru = computed(() => user.value?.role === 'guru')

const learningCards = computed(() => studentLearning.value.map((item) => ({
  ...item,
  icon: item.slug === 'warna' || item.slug === 'berhitung' || item.slug === 'matematika' ? '🔢' : item.slug === 'bentuk' ? '📐' : '📖',
  accent: item.slug === 'bahasa' ? 'orange' : item.slug === 'matematika' ? 'blue' : 'violet',
})))

const calendarCells = computed(() => {
  const monthData = studentDashboard.value?.calendar?.days || []
  if (monthData.length) {
    return monthData.map((day) => ({
      ...day,
      dayLabel: day.day || '',
      isCurrentMonth: day.in_month !== false,
    }))
  }

  const today = new Date()
  const year = today.getFullYear()
  const month = today.getMonth()
  const firstDay = new Date(year, month, 1)
  const startOffset = (firstDay.getDay() + 6) % 7
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const cells = []

  for (let index = 0; index < 42; index += 1) {
    const dayNumber = index - startOffset + 1
    if (dayNumber <= 0 || dayNumber > daysInMonth) {
      cells.push({ day: '', date: '', in_month: false, is_today: false, is_active: false, dayLabel: '' })
      continue
    }

    const date = new Date(year, month, dayNumber)
    const dateKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`

    cells.push({
      day: date.getDate(),
      date: dateKey,
      in_month: true,
      is_today: dateKey === today.toISOString().slice(0, 10),
      is_active: false,
      dayLabel: String(date.getDate()),
    })
  }

  return cells
})

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '',
  headers: { Accept: 'application/json' },
})

const distribution = computed(() => {
  if (!stats.value) return []

  const total = stats.value.total_pengguna || 1
  return [
    { label: 'Pendamping', value: stats.value.pendamping, color: 'bg-emerald-400' },
    { label: 'Guru', value: stats.value.guru, color: 'bg-violet-500' },
    { label: 'Admin', value: stats.value.admin, color: 'bg-sky-400' },
  ].map((item) => ({ ...item, percentage: Math.round((item.value / total) * 100) }))
})

const filteredStudents = computed(() => guruStudents.value)

watch(studentSearch, (value) => {
  const query = value.trim()
  if (query === '') {
    loadGuruStudents()
    return
  }

  loadGuruStudents(query)
})

const studentAge = computed(() => {
  if (!tokenForm.value.tanggal_lahir) return ''
  const birthDate = new Date(tokenForm.value.tanggal_lahir)
  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDifference = today.getMonth() - birthDate.getMonth()
  if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) age -= 1
  return age >= 0 ? `${age} tahun` : ''
})

function go(path) {
  window.history.pushState({}, '', path)
  currentView.value = path.includes('/admin/tokens') ? 'tokens' : path.includes('/admin/users') ? 'users' : 'dashboard'
}

async function loadStudentLearning() {
  try {
    studentLearning.value = (await api.get('/api/pendamping/learning')).data.data
  } catch (error) {
    message.value = error.response?.data?.message ?? 'Materi belum dapat dimuat.'
  }
}

async function login() {
  message.value = ''
  isLoading.value = true

  try {
    const response = loginMode.value === 'pendamping'
      ? await api.post('/api/auth/login-pendamping', { token: accessToken.value })
      : await api.post('/api/auth/login', { username: username.value, password: password.value })

    const token = response.data.token
    localStorage.setItem('smart_learning_token', token)
    api.defaults.headers.common.Authorization = `Bearer ${token}`
    if (loginMode.value === 'pendamping') {
      user.value = { ...response.data.student, sessionType: 'pendamping' }
      studentDashboard.value = (await api.get('/api/pendamping/dashboard')).data.data
      studentLearning.value = (await api.get('/api/pendamping/learning')).data.data
      await loadStudentQuizzes()
      go('/pendamping')
    } else {
      user.value = response.data.user
      if (user.value.role === 'guru') {
        guruDashboard.value = (await api.get('/api/guru/dashboard')).data.data
        guruMaterials.value = (await api.get('/api/guru/materials')).data.data
        guruQuizzes.value = (await api.get('/api/guru/quizzes')).data.data
        guruProgress.value = (await api.get('/api/guru/progress')).data.data
        await loadGuruStudents()
        go('/guru/dashboard')
      } else {
        const dashboard = await api.get('/api/admin/dashboard')
        stats.value = dashboard.data.data
        go('/admin/dashboard')
      }
    }
  } catch (error) {
    message.value = error.response?.data?.message ?? 'Backend tidak dapat dihubungi.'
  } finally {
    isLoading.value = false
  }
}

async function restoreSession() {
  const token = localStorage.getItem('smart_learning_token')
  if (!token) return

  try {
    api.defaults.headers.common.Authorization = `Bearer ${token}`
    const response = await api.get('/api/user')
    user.value = response.data
    if (response.data.nama) {
      user.value = { ...response.data, sessionType: 'pendamping' }
      studentDashboard.value = (await api.get('/api/pendamping/dashboard')).data.data
      studentLearning.value = (await api.get('/api/pendamping/learning')).data.data
      await loadStudentQuizzes()
    } else {
      if (response.data.role === 'guru') {
        guruDashboard.value = (await api.get('/api/guru/dashboard')).data.data
        guruMaterials.value = (await api.get('/api/guru/materials')).data.data
        guruQuizzes.value = (await api.get('/api/guru/quizzes')).data.data
        guruProgress.value = (await api.get('/api/guru/progress')).data.data
        await loadGuruStudents()
      } else {
        const dashboard = await api.get('/api/admin/dashboard')
        stats.value = dashboard.data.data
      }
    }
  } catch {
    localStorage.removeItem('smart_learning_token')
    delete api.defaults.headers.common.Authorization
  }
}

async function logout() {
  const token = localStorage.getItem('smart_learning_token')
  if (token) await api.post('/api/auth/logout').catch(() => {})
  localStorage.removeItem('smart_learning_token')
  delete api.defaults.headers.common.Authorization
  user.value = null
  stats.value = null
  studentDashboard.value = null
  guruDashboard.value = null
  guruProgress.value = null
  guruStudents.value = []
  selectedStudentDetail.value = null
  selectedStudentId.value = null
  selectedTab.value = 'basic'
  studentSearch.value = ''
  guruMaterials.value = []
  guruQuizzes.value = []
  studentQuizzes.value = []
  activeQuiz.value = null
  quizResult.value = null
  guruView.value = 'home'
  studentLearning.value = []
  studentView.value = 'home'
  username.value = ''
  password.value = ''
  accessToken.value = ''
  message.value = ''
}

function setMaterialFile(event) {
  materialForm.value.file = event.target.files[0] ?? null
}

async function uploadMaterial() {
  materialMessage.value = ''
  materialError.value = false
  isUploadingMaterial.value = true
  const formData = new FormData()
  formData.append('title', materialForm.value.title)
  formData.append('category_id', materialForm.value.category_id)
  formData.append('type', materialForm.value.type)
  if (materialForm.value.description) formData.append('description', materialForm.value.description)
  if (materialForm.value.file) formData.append('file', materialForm.value.file)
  if (materialForm.value.youtube_url) formData.append('youtube_url', materialForm.value.youtube_url)

  try {
    await api.post('/api/guru/materials', formData)
    guruMaterials.value = (await api.get('/api/guru/materials')).data.data
    guruDashboard.value = (await api.get('/api/guru/dashboard')).data.data
    materialMessage.value = 'Materi berhasil diunggah dan langsung tersedia untuk pendamping siswa.'
    materialForm.value = { title: '', category_id: '', type: 'pdf', file: null, youtube_url: '', description: '' }
  } catch (error) {
    materialError.value = true
    const validationErrors = error.response?.data?.errors
    materialMessage.value = validationErrors ? Object.values(validationErrors).flat()[0] : (error.response?.data?.message ?? 'Materi gagal diunggah.')
  } finally {
    isUploadingMaterial.value = false
  }
}

function addQuizQuestion() {
  quizForm.value.questions.push({ question_text: '', answers: [{ answer_text: '', is_correct: true }, { answer_text: '', is_correct: false }] })
}

function addQuizAnswer(question) {
  question.answers.push({ answer_text: '', is_correct: false })
}

async function saveQuiz() {
  quizMessage.value = ''
  quizError.value = false
  isSavingQuiz.value = true
  try {
    await api.post('/api/guru/quizzes', quizForm.value)
    guruQuizzes.value = (await api.get('/api/guru/quizzes')).data.data
    quizMessage.value = 'Kuis berhasil dibuat dan siap dikerjakan pendamping.'
    quizForm.value = { material_id: '', title: '', description: '', passing_score: 70, questions: [{ question_text: '', answers: [{ answer_text: '', is_correct: true }, { answer_text: '', is_correct: false }] }] }
  } catch (error) {
    quizError.value = true
    const errors = error.response?.data?.errors
    quizMessage.value = errors ? Object.values(errors).flat()[0] : (error.response?.data?.message ?? 'Kuis gagal dibuat.')
  } finally {
    isSavingQuiz.value = false
  }

  async function loadGuruProgress() {
    guruProgress.value = (await api.get('/api/guru/progress')).data.data
  }
}

async function loadStudentQuizzes() {
  studentQuizzes.value = (await api.get('/api/pendamping/quizzes')).data.data
}

async function loadGuruStudents(search = '') {
  try {
    const params = search.trim() ? { search: search.trim() } : {}
    const response = await api.get('/api/guru/students', { params })
    guruStudents.value = response.data.data || []
    selectedStudentDetail.value = null
    selectedStudentId.value = null
    selectedTab.value = 'basic'
  } catch (error) {
    guruStudents.value = []
    selectedStudentDetail.value = null
    message.value = error.response?.data?.message ?? 'Daftar siswa gagal dimuat.'
  }
}

async function loadStudentDetail(studentId) {
  selectedStudentId.value = studentId
  selectedTab.value = 'basic'
  try {
    const response = await api.get(`/api/guru/students/${studentId}`)
    selectedStudentDetail.value = response.data.data
  } catch (error) {
    selectedStudentDetail.value = null
    message.value = error.response?.data?.message ?? 'Detail siswa gagal dimuat.'
  }
}

async function submitQuiz() {
  quizResult.value = (await api.post(`/api/pendamping/quizzes/${activeQuiz.value.id}/submit`, { answers: quizAnswers.value })).data.data
}

async function openStudentMaterial(material) {
  selectedMaterial.value = material
  await loadStudentLearning()
}

async function askAssistant(question = assistantQuestion.value) {
  const trimmed = question.trim()
  if (!trimmed || assistantLoading.value) return
  assistantError.value = ''
  assistantQuestion.value = ''
  assistantMessages.value.push({ role: 'user', text: trimmed })
  assistantLoading.value = true
  try {
    const response = await api.post('/api/pendamping/ai/ask', {
      question: trimmed,
      material_id: selectedMaterial.value?.id,
      material_title: selectedMaterial.value?.title,
    })
    assistantMessages.value.push({ role: 'assistant', text: response.data.data.answer })
  } catch (error) {
    const text = error.response?.data?.message ?? 'Asisten sedang beristirahat. Coba lagi sebentar, ya.'
    assistantError.value = text
    assistantMessages.value.push({ role: 'assistant', text })
  } finally {
    assistantLoading.value = false
  }
}

watch(selectedMaterial, async (material) => {
  if (!material) return
  await api.post(`/api/pendamping/materials/${material.id}/access`).catch(() => {})
})

async function generateToken() {
  tokenMessage.value = ''
  tokenError.value = false
  generatedToken.value = ''
  isGeneratingToken.value = true

  try {
    const studentResponse = await api.post('/api/admin/students', {
      nama: tokenForm.value.nama,
      tanggal_lahir: tokenForm.value.tanggal_lahir,
      tempat_lahir: tokenForm.value.tempat_lahir,
      nama_orang_tua_wali: tokenForm.value.nama_orang_tua_wali,
      pendamping_email: tokenForm.value.pendamping_email || null,
      pendamping_phone: tokenForm.value.pendamping_phone || null,
    })
    const studentId = studentResponse.data.data.id
    const tokenResponse = await api.post(`/api/admin/students/${studentId}/generate-token`)
    generatedToken.value = tokenResponse.data.token
    tokenMessage.value = tokenResponse.data.message
    tokenError.value = tokenResponse.data.email_sent === false
    tokenForm.value = { nama: '', tempat_lahir: '', tanggal_lahir: '', gender: '', disability: '', nama_orang_tua_wali: '', pendamping_email: '', pendamping_phone: '' }
    const dashboard = await api.get('/api/admin/dashboard')
    stats.value = dashboard.data.data
  } catch (error) {
    tokenError.value = true
    const validationErrors = error.response?.data?.errors
    tokenMessage.value = validationErrors ? Object.values(validationErrors).flat()[0] : (error.response?.data?.message ?? 'Token gagal dibuat.')
  } finally {
    isGeneratingToken.value = false
  }
}

onMounted(restoreSession)
</script>

<template>
  <main v-if="!user" class="min-h-screen bg-slate-50 px-6 py-12 text-slate-900">
    <section class="mx-auto grid min-h-[70vh] max-w-5xl items-center gap-10 lg:grid-cols-[1.2fr_0.8fr]">
      <div>
        <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Smart Learning</p>
        <h1 class="max-w-2xl text-4xl font-black tracking-tight sm:text-6xl">Belajar dengan cara yang menyenangkan.</h1>
        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">Platform pendampingan belajar visual untuk siswa Down Syndrome, guru, admin, dan pendamping.</p>
      </div>

      <form class="rounded-3xl bg-white p-8 shadow-xl shadow-teal-900/10" @submit.prevent="login">
        <h2 class="text-2xl font-black">Masuk ke aplikasi</h2>
        <div class="mt-5 grid grid-cols-2 rounded-xl bg-slate-100 p-1 text-sm font-bold">
           <button type="button" class="rounded-lg px-3 py-2" :class="loginMode === 'staff' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'" @click="loginMode = 'staff'; message = ''">Admin / Guru</button>
          <button type="button" class="rounded-lg px-3 py-2" :class="loginMode === 'pendamping' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'" @click="loginMode = 'pendamping'; message = ''">Pendamping Siswa</button>
        </div>
        <p class="mt-4 text-sm text-slate-500">{{ loginMode === 'staff' ? 'Gunakan akun admin atau guru yang terdaftar di backend.' : 'Masukkan token akses yang diterima oleh pendamping.' }}</p>
        <template v-if="loginMode === 'staff'">
          <label class="mt-6 block text-sm font-bold" for="username">Username</label>
          <input id="username" v-model="username" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" required autocomplete="username" />
          <label class="mt-4 block text-sm font-bold" for="password">Password</label>
          <input id="password" v-model="password" type="password" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" required autocomplete="current-password" />
        </template>
        <template v-else>
          <label class="mt-6 block text-sm font-bold" for="access-token">Token Akses</label>
          <input id="access-token" v-model="accessToken" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 font-mono uppercase tracking-wider outline-none focus:border-teal-600" required placeholder="Contoh: SL-..." autocomplete="one-time-code" />
        </template>
        <button class="mt-6 min-h-12 w-full rounded-xl bg-teal-700 px-6 font-bold text-white transition hover:bg-teal-800 disabled:cursor-wait disabled:opacity-60" :disabled="isLoading">
           {{ isLoading ? 'Menghubungkan...' : 'Masuk' }}
        </button>
        <p v-if="message" class="mt-4 text-sm font-semibold text-rose-600">{{ message }}</p>
      </form>
    </section>
  </main>

  <main v-else-if="isPendamping" class="min-h-screen bg-slate-50 text-slate-900">
    <div class="flex min-h-screen flex-col lg:flex-row">
      <aside class="w-full border-b border-slate-200 bg-white p-5 lg:min-h-screen lg:w-60 lg:border-b-0 lg:border-r">
        <div class="flex items-center gap-3 lg:mb-10">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-100 text-2xl">🦁</div>
          <div><p class="font-black text-sky-600">Smart Learning</p><p class="text-xs font-bold text-slate-500">Down Syndrome</p></div>
        </div>
        <nav class="mt-5 grid grid-cols-4 gap-2 text-xs font-bold lg:block lg:space-y-2">
          <button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="studentView === 'home' ? 'bg-blue-600 text-white' : ''" @click="studentView = 'home'">⌂ <span class="lg:ml-2">Beranda</span></button>
          <button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="studentView === 'learning' ? 'bg-blue-600 text-white' : ''" @click="studentView = 'learning'; loadStudentLearning()">▣ <span class="lg:ml-2">Belajar</span></button>
          <button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="studentView === 'quiz' ? 'bg-blue-600 text-white' : ''" @click="studentView = 'quiz'; loadStudentQuizzes()">◉ <span class="lg:ml-2">Kuis</span></button>
          <button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="studentView === 'assistant' ? 'bg-blue-600 text-white' : ''" @click="studentView = 'assistant'">✦ <span class="lg:ml-2">Tanya AI</span></button>
        </nav>
        <div class="mt-10 hidden rounded-2xl bg-sky-50 p-4 text-center text-xs font-semibold text-slate-600 lg:block">Belajar setiap hari membuat kita lebih baik! 💙</div>
      </aside>

      <section v-if="studentView === 'home'" class="flex-1 p-5 sm:p-8">
        <div class="mx-auto max-w-7xl">
          <header class="flex items-start justify-between">
            <div><p class="text-sm font-bold text-slate-500">Selamat sore! 👋</p><h1 class="text-3xl font-black sm:text-4xl">Halo, {{ user.nama }}!</h1><p class="mt-1 text-sm text-slate-500">Semangat belajar hari ini! Kamu hebat! 🌟</p></div>
            <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold" @click="logout">Keluar</button>
          </header>

          <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-6">
              <section class="rounded-3xl bg-gradient-to-r from-blue-600 to-sky-500 p-6 text-white shadow-lg shadow-blue-200">
                <div class="flex items-center justify-between gap-4"><div><p class="text-3xl font-black">{{ studentDashboard?.level.name }}</p><p class="mt-2 font-semibold text-blue-100">{{ studentDashboard?.level.points }}/{{ studentDashboard?.level.target_points }} XP · {{ studentDashboard?.level.next_level ? `${studentDashboard.level.target_points - studentDashboard.level.points} XP lagi naik!` : 'Level tertinggi' }}</p></div><div class="text-6xl">🚀</div></div>
                <div class="mt-5 h-3 overflow-hidden rounded-full bg-blue-300/60"><div class="h-full rounded-full bg-white" :style="{ width: `${studentDashboard?.level.progress ?? 0}%` }"></div></div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs font-bold"><div class="rounded-xl bg-white/15 p-3">🔥<br>{{ studentDashboard?.streak_days }} Hari Streak</div><div class="rounded-xl bg-white/15 p-3">🏆<br>{{ studentDashboard?.completed_quizzes }} Kuis Selesai</div><div class="rounded-xl bg-white/15 p-3">◷<br>{{ studentDashboard?.weekly_minutes }} menit belajar</div></div>
              </section>

              <section><h2 class="text-xl font-black">Mulai Belajar! 🚀</h2><div class="mt-4 grid gap-4 sm:grid-cols-3"><button class="rounded-2xl bg-blue-50 p-5 text-left ring-1 ring-blue-100"><span class="text-3xl">📖</span><p class="mt-3 font-black text-blue-700">Belajar</p><p class="text-xs text-slate-500">Materi pembelajaran</p></button><button class="rounded-2xl bg-emerald-50 p-5 text-left ring-1 ring-emerald-100"><span class="text-3xl">🧠</span><p class="mt-3 font-black text-emerald-600">Kuis</p><p class="text-xs text-slate-500">Uji pemahamanmu</p></button><button class="rounded-2xl bg-violet-50 p-5 text-left ring-1 ring-violet-100"><span class="text-3xl">🤖</span><p class="mt-3 font-black text-violet-600">Tanya AI</p><p class="text-xs text-slate-500">Asisten belajarmu</p></button></div></section>

              <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><div class="flex items-center justify-between"><div><h2 class="font-black">Tantangan Hari Ini! 🎯</h2><p class="mt-1 text-sm text-slate-600">Selesaikan 1 kuis untuk mendapatkan poin tambahan.</p></div><strong class="text-xl text-orange-600">+100<br><span class="text-xs">poin</span></strong></div></section>

              <section><h2 class="text-xl font-black">Pelajaran 📚</h2><div class="mt-3 space-y-3"><div v-for="item in studentDashboard?.progress" :key="item.slug" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200"><div class="flex justify-between text-sm font-bold"><span>{{ item.name }}</span><span class="text-blue-600">{{ item.percentage }}%</span></div><div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" :style="{ width: `${item.percentage}%` }"></div></div><p class="mt-1 text-right text-xs text-slate-400">{{ item.completed }}/{{ item.total }}</p></div><p v-if="!studentDashboard?.progress?.length" class="rounded-2xl bg-white p-5 text-sm text-slate-500 ring-1 ring-slate-200">Belum ada progres pelajaran. Yuk mulai belajar hari ini!</p></div></section>
            </div>

            <aside class="space-y-6"><section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex items-center justify-between"><h2 class="font-black">Kalender Belajar</h2><span class="text-xl">📅</span></div><p class="mt-5 text-center text-sm font-bold text-slate-500">{{ studentDashboard?.calendar?.month_label || new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date()) }}</p><div class="mt-4 grid grid-cols-7 gap-2 text-center text-xs text-slate-500"><span v-for="label in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="label" class="font-black">{{ label }}</span><span v-for="(dayItem, index) in calendarCells" :key="dayItem.date || `empty-${index}`" class="rounded-lg p-1" :class="dayItem.is_today ? 'bg-blue-500 font-black text-white' : dayItem.is_active ? 'bg-emerald-100 font-black text-emerald-700' : dayItem.day ? 'text-slate-600' : 'text-transparent'">{{ dayItem.day || '' }}</span></div></section><section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex justify-between"><h2 class="font-black">Lencana Terbaru</h2><span class="text-xs font-bold text-blue-600">{{ studentDashboard?.available_badges }} terbuka</span></div><div class="mt-5 flex gap-3"><div v-for="badge in studentDashboard?.badges" :key="badge.id" class="flex-1 text-center"><div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-amber-100 text-2xl">🏅</div><p class="mt-2 text-xs font-bold">{{ badge.name }}</p></div><p v-if="!studentDashboard?.badges?.length" class="text-sm text-slate-500">Lencana akan muncul setelah mencapai target.</p></div></section><section class="rounded-2xl bg-violet-50 p-5 ring-1 ring-violet-100"><h2 class="font-black">🤖 Asisten AI</h2><p class="mt-3 rounded-xl bg-white p-3 text-sm text-slate-600">Hai, {{ user.nama }}! Ada yang bisa AI bantu hari ini?</p><div class="mt-3 flex gap-2"><input class="min-w-0 flex-1 rounded-xl border-0 px-3 py-2 text-xs" placeholder="Ketik pertanyaanmu..." /><button class="rounded-xl bg-blue-600 px-3 text-white">➤</button></div></section></aside>
          </div>
        </div>
      </section>

      <section v-else-if="studentView === 'quiz'" class="flex-1 p-5 sm:p-8"><div class="mx-auto max-w-4xl"><header><p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">Latihan</p><h1 class="mt-2 text-3xl font-black">Kuis Pembelajaran</h1><p class="mt-1 text-slate-500">Uji pemahamanmu dari materi yang sudah dipelajari.</p></header><div v-if="!activeQuiz" class="mt-7 grid gap-4 sm:grid-cols-2"><button v-for="quiz in studentQuizzes" :key="quiz.id" class="rounded-2xl bg-white p-5 text-left shadow-sm ring-1 ring-slate-200 hover:ring-blue-400" @click="activeQuiz = quiz; quizAnswers = {}; quizResult = null"><p class="text-lg font-black text-blue-700">{{ quiz.title }}</p><p class="mt-1 text-sm text-slate-500">{{ quiz.material?.title }} · {{ quiz.questions.length }} soal</p><span class="mt-4 inline-block rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Mulai kuis →</span></button><p v-if="!studentQuizzes.length" class="rounded-2xl bg-white p-6 text-sm text-slate-500 ring-1 ring-slate-200">Belum ada kuis dari guru.</p></div><form v-else class="mt-7 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200" @submit.prevent="submitQuiz"><div class="flex items-center justify-between"><h2 class="text-xl font-black">{{ activeQuiz.title }}</h2><button type="button" class="text-sm font-bold text-slate-400" @click="activeQuiz = null">Kembali</button></div><div v-for="(question, index) in activeQuiz.questions" :key="question.id" class="mt-6 rounded-xl bg-slate-50 p-4"><p class="font-black">{{ index + 1 }}. {{ question.question_text }}</p><label v-for="answer in question.answers" :key="answer.id" class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl bg-white p-3 text-sm ring-1 ring-slate-200"><input v-model="quizAnswers[question.id]" type="radio" :name="`quiz-question-${question.id}`" :value="answer.id" required />{{ answer.answer_text }}</label></div><button v-if="!quizResult" class="mt-6 min-h-11 w-full rounded-xl bg-blue-600 font-bold text-white">Kirim Jawaban</button><div v-if="quizResult" class="mt-6 rounded-xl p-4" :class="quizResult.passed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"><p class="text-2xl font-black">Nilai {{ quizResult.score }}</p><p class="mt-1">{{ quizResult.correct_answers }}/{{ quizResult.total_questions }} jawaban benar.</p><button type="button" class="mt-3 font-bold underline" @click="activeQuiz = null">Pilih kuis lain</button></div></form></div></section>
      <section v-else-if="studentView === 'assistant'" class="flex-1 bg-sky-50 p-5 sm:p-8"><div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl flex-col"><header class="rounded-b-[2.5rem] bg-gradient-to-br from-emerald-400 to-teal-400 p-6 text-white shadow-lg"><div class="flex items-center gap-4"><div class="grid h-16 w-16 place-items-center rounded-2xl bg-white text-4xl shadow">🤖</div><div><h1 class="text-3xl font-black">MathBot</h1><p class="font-semibold text-emerald-50">AI Learning Friend</p></div><span class="ml-auto rounded-full bg-white/20 px-4 py-2 text-sm font-bold">🟢 Online</span></div><div class="mt-6 grid grid-cols-3 gap-2 text-center text-sm font-bold"><button v-for="difficulty in [{ id: 'easy', label: '🌱 Easy' }, { id: 'medium', label: '🌿 Medium' }, { id: 'hard', label: '🌳 Hard' }]" :key="difficulty.id" class="rounded-full px-3 py-2" :class="assistantDifficulty === difficulty.id ? 'bg-white text-slate-700' : 'bg-white/15 text-white'" @click="assistantDifficulty = difficulty.id">{{ difficulty.label }}</button></div></header><div class="flex-1 space-y-4 overflow-y-auto py-6"><div v-for="(item, index) in assistantMessages" :key="index" class="flex gap-3" :class="item.role === 'user' ? 'justify-end' : 'justify-start'"><div v-if="item.role === 'assistant'" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-400 text-xl">🤖</div><p class="max-w-[80%] rounded-2xl p-4 text-sm leading-6 shadow-sm" :class="item.role === 'user' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200'">{{ item.text }}</p></div><p v-if="assistantLoading" class="ml-14 text-sm font-semibold text-slate-500">MathBot sedang berpikir...</p></div><div class="space-y-3 border-t border-slate-200 bg-sky-50 pt-4"><div class="flex gap-2 overflow-x-auto"><button v-for="suggestion in ['What is 2 + 2?', 'Bantu aku menghitung!', 'Jelaskan materi matematika']" :key="suggestion" class="whitespace-nowrap rounded-full border-2 border-blue-400 bg-white px-4 py-2 text-sm font-bold text-blue-500" @click="askAssistant(suggestion)">{{ suggestion }}</button></div><form class="flex gap-3" @submit.prevent="askAssistant()"><input v-model="assistantQuestion" class="min-h-14 min-w-0 flex-1 rounded-2xl border-0 bg-white px-4 shadow-sm outline-none ring-1 ring-slate-200" placeholder="Tanya apa saja tentang materi belajar..." /><button class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-blue-500 text-2xl font-black text-white shadow-lg disabled:opacity-50" :disabled="assistantLoading || !assistantQuestion.trim()">➤</button></form><p v-if="assistantError" class="text-xs font-semibold text-rose-600">Pertanyaan dibatasi pada materi pembelajaran agar tetap aman.</p></div></div></section>

      <section v-else class="flex-1 p-5 sm:p-8">
        <div class="mx-auto max-w-5xl">
          <header class="flex items-center gap-4"><div class="grid h-12 w-12 place-items-center rounded-full bg-blue-100 text-3xl">📖</div><div><h1 class="text-3xl font-black">Pilih Pelajaran</h1><p class="mt-1 text-sm text-slate-500">Pilih mata pelajaran yang ingin kamu pelajari hari ini!</p></div></header>
          <div class="mt-8 grid gap-6 md:grid-cols-2">
            <article v-for="card in learningCards" :key="card.id" class="rounded-3xl p-7 shadow-sm ring-1" :class="card.accent === 'orange' ? 'bg-orange-50 ring-orange-200' : card.accent === 'blue' ? 'bg-blue-50 ring-blue-300' : 'bg-violet-50 ring-violet-200'">
              <div class="text-center"><div class="text-6xl">{{ card.icon }}</div><h2 class="mt-4 text-2xl font-black" :class="card.accent === 'orange' ? 'text-orange-600' : card.accent === 'blue' ? 'text-blue-600' : 'text-violet-600'">{{ card.name }}</h2><p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-slate-500">{{ card.description || 'Belajar dengan latihan sederhana dan menyenangkan.' }}</p><div class="mx-auto mt-5 max-w-xs"><div class="h-2 rounded-full bg-white/80"><div class="h-full rounded-full" :class="card.accent === 'orange' ? 'bg-orange-500' : card.accent === 'blue' ? 'bg-blue-500' : 'bg-violet-500'" :style="{ width: `${card.progress}%` }"></div></div><p class="mt-2 text-sm font-black" :class="card.accent === 'orange' ? 'text-orange-600' : card.accent === 'blue' ? 'text-blue-600' : 'text-violet-600'">{{ card.progress }}%</p></div><button class="mt-5 min-h-12 w-full rounded-xl px-5 font-black text-white" :class="card.accent === 'orange' ? 'bg-orange-500' : card.accent === 'blue' ? 'bg-blue-600' : 'bg-violet-600'" @click="selectedLearning = card">Pilih {{ card.name }} <span class="ml-2">→</span></button></div>
              <div v-if="card.materials.length" class="mt-5 border-t border-black/10 pt-4"><p class="text-xs font-black uppercase tracking-wider text-slate-500">Materi tersedia</p><p v-for="material in card.materials.slice(0, 2)" :key="material.id" class="mt-2 rounded-lg bg-white/70 px-3 py-2 text-sm font-bold text-slate-600">{{ material.title }}</p></div>
              <p v-else class="mt-5 text-center text-xs font-semibold text-slate-400">Materi akan segera tersedia.</p>
            </article>
            <p v-if="!learningCards.length" class="rounded-2xl bg-white p-6 text-sm font-semibold text-rose-600 ring-1 ring-slate-200">Materi belum termuat. Klik menu Belajar lagi atau refresh halaman.</p>
          </div>
          <section v-if="selectedLearning" class="mt-7 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><div class="flex items-center justify-between"><h2 class="text-xl font-black">Materi {{ selectedLearning.name }}</h2><button class="text-sm font-bold text-slate-400" @click="selectedLearning = null; selectedMaterial = null">Tutup</button></div><div class="mt-4 grid gap-3 sm:grid-cols-2"><button v-for="material in selectedLearning.materials" :key="material.id" class="rounded-xl bg-slate-50 p-4 text-left ring-1 ring-slate-200 hover:bg-blue-50" @click="selectedMaterial = material"><p class="font-black text-blue-700">{{ material.title }}</p><p class="mt-1 text-xs text-slate-500">{{ material.description }}</p><span class="mt-3 inline-block text-xs font-bold text-blue-600">Mulai belajar →</span></button></div><article v-if="selectedMaterial" class="mt-5 rounded-2xl bg-blue-50 p-5"><p class="text-xs font-black uppercase tracking-wider text-blue-600">Materi sedang dibuka</p><h3 class="mt-2 text-2xl font-black text-slate-900">{{ selectedMaterial.title }}</h3><p class="mt-2 leading-7 text-slate-600">{{ selectedMaterial.content || selectedMaterial.description }}</p><div class="mt-5 flex flex-wrap gap-3"><a v-for="resource in selectedMaterial.resources" :key="resource.type" :href="resource.url" target="_blank" rel="noreferrer" class="rounded-xl px-4 py-3 text-sm font-bold text-white" :class="resource.type === 'video' ? 'bg-rose-500' : resource.type === 'ppt' ? 'bg-orange-500' : 'bg-blue-600'">{{ resource.type === 'video' ? '▶' : '↓' }} {{ resource.label }}</a></div><button class="mt-4 rounded-xl bg-blue-600 px-5 py-3 font-bold text-white">Mulai latihan</button></article></section>
          <div class="mt-7 rounded-2xl bg-blue-50 p-5 text-center text-sm font-semibold text-blue-800">⭐ Kamu hebat! Belajar setiap hari membuat kamu semakin pintar dan percaya diri.</div>
        </div>
      </section>
    </div>
  </main>

  <main v-else-if="isGuru" class="min-h-screen bg-slate-50 text-slate-900">
    <div class="flex min-h-screen flex-col lg:flex-row">
      <aside class="w-full border-b border-slate-200 bg-white p-5 lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
        <div class="flex items-center gap-3 lg:mb-10"><div class="grid h-11 w-11 place-items-center rounded-2xl bg-amber-100 text-2xl">🦁</div><div><p class="font-black text-sky-600">Smart Learning</p><p class="text-xs font-bold text-slate-500">Guru Panel</p></div></div>
        <nav class="mt-5 grid grid-cols-4 gap-2 text-xs font-bold lg:block lg:space-y-2"><button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="guruView === 'home' ? 'bg-blue-600 text-white' : ''" @click="guruView = 'home'">⌂ <span class="lg:ml-2">Beranda</span></button><button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="guruView === 'students' ? 'bg-blue-600 text-white' : ''" @click="guruView = 'students'; if (!guruStudents.length) loadGuruStudents()">👥 <span class="lg:ml-2">Siswa</span></button><button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="guruView === 'materials' ? 'bg-blue-600 text-white' : ''" @click="guruView = 'materials'">📚 <span class="lg:ml-2">Materi</span></button><button class="rounded-xl px-3 py-3 text-slate-600 hover:bg-slate-50 lg:block lg:w-full lg:text-left" :class="guruView === 'progress' ? 'bg-blue-600 text-white' : ''" @click="guruView = 'progress'; loadGuruProgress()">📈 <span class="lg:ml-2">Progress</span></button></nav>
        <div class="mt-10 hidden rounded-2xl bg-blue-50 p-4 text-center text-xs font-semibold text-slate-600 lg:block">Pantau perkembangan siswa dan bantu mereka belajar lebih baik.</div>
      </aside>
      <section class="flex-1 p-5 sm:p-8"><div class="mx-auto max-w-7xl"><header class="flex items-start justify-between"><div><p class="text-sm font-bold text-slate-500">Selamat datang, Guru 👋</p><h1 class="text-3xl font-black sm:text-4xl">Halo, {{ user.name }}!</h1><p class="mt-1 text-sm text-slate-500">Pantau aktivitas dan progres belajar siswa hari ini.</p></div><button class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600" @click="logout">Keluar</button></header>
        <div v-if="guruView === 'home'" class="mt-7 space-y-6">
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in [{ label: 'Total Siswa', value: guruDashboard?.total_students, icon: '🧑‍🎓', color: 'text-sky-500' }, { label: 'Aktif Hari Ini', value: guruDashboard?.active_students, icon: '✅', color: 'text-emerald-500' }, { label: 'Total Materi', value: guruDashboard?.total_materials, icon: '📚', color: 'text-violet-500' }, { label: 'Rata-rata Progress', value: `${Math.round((guruDashboard?.students || []).reduce((sum, item) => sum + (item.progress || 0), 0) / Math.max((guruDashboard?.students || []).length, 1))}%`, icon: '📈', color: 'text-amber-500' }]" :key="card.label" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
              <div class="flex items-center justify-between gap-3"><span class="text-3xl">{{ card.icon }}</span><span class="text-3xl font-black" :class="card.color">{{ card.value ?? 0 }}</span></div>
              <p class="mt-3 text-sm font-bold text-slate-500">{{ card.label }}</p>
            </article>
          </div>

          <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
              <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-black">Aktivitas Terbaru</h2>
                <button type="button" class="text-sm font-bold text-blue-600" @click="guruView = 'students'">Lihat semua</button>
              </div>

              <div v-if="guruDashboard?.recent_activities?.length" class="space-y-3">
                <div v-for="activity in guruDashboard.recent_activities" :key="`${activity.student}-${activity.activity}-${activity.completed_at}`" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                  <div class="grid h-10 w-10 place-items-center rounded-full bg-amber-100 text-lg">👤</div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-black text-slate-800">{{ activity.student }}</p>
                    <p class="text-sm text-slate-600">{{ activity.activity }}</p>
                  </div>
                  <div class="text-right">
                    <p v-if="activity.score !== null && activity.score !== undefined" class="text-sm font-black text-violet-700">{{ activity.score }}</p>
                    <p class="text-[11px] font-bold text-slate-500">{{ new Date(activity.completed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</p>
                  </div>
                </div>
              </div>
              <p v-else class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 ring-1 ring-slate-200">Belum ada aktivitas siswa saat ini.</p>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
              <h2 class="text-xl font-black">Statistik Pembelajaran</h2>
              <div class="mt-5 space-y-4">
                <div v-for="subject in (guruProgress?.subject_performance || [{ name: 'Matematika', progress: 65 }, { name: 'Bahasa', progress: 58 }, { name: 'Warna', progress: 70 }])" :key="subject.name" class="space-y-2">
                  <div class="flex items-center justify-between text-sm">
                    <span class="font-bold text-slate-600">{{ subject.name }}</span>
                    <span class="font-black text-violet-700">{{ subject.progress }}%</span>
                  </div>
                  <div class="h-2.5 rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-violet-500" :style="{ width: `${Math.max(subject.progress || 0, 8)}%` }"></div>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between"><h2 class="text-xl font-black">Siswa Aktif Hari Ini 🟢</h2><button class="text-sm font-bold text-blue-600" @click="guruView = 'students'">Lihat semua</button></div>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              <article v-for="student in guruDashboard?.students" :key="student.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-3"><div class="grid h-12 w-12 place-items-center rounded-full bg-amber-100 text-2xl">🧒</div><div class="min-w-0 flex-1"><p class="truncate font-black">{{ student.name }}</p><p class="text-xs text-slate-500">{{ student.level }}</p></div><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-black text-blue-600">{{ student.progress }}%</span></div>
                <div class="mt-4 h-2 rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-400" :style="{ width: `${student.progress}%` }"></div></div>
                <div class="mt-2 flex justify-between text-xs text-slate-500"><span>{{ student.points }} poin</span><span>Progres belajar</span></div>
              </article>
              <p v-if="!guruDashboard?.students?.length" class="rounded-2xl bg-white p-6 text-sm text-slate-500 ring-1 ring-slate-200">Belum ada data siswa.</p>
            </div>
          </section>
        </div>

        <section v-else-if="guruView === 'students'" class="mt-7">
          <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-600">Siswa</p>
              <h2 class="mt-2 text-3xl font-black">Daftar Siswa</h2>
            </div>
            <div class="relative md:w-72">
              <input v-model="studentSearch" placeholder="Cari siswa..." class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-4 pr-10 text-sm outline-none focus:border-sky-500" />
              <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
            </div>
          </div>

          <div v-if="!selectedStudentDetail" class="mt-6 space-y-4">
            <div v-for="(student, index) in filteredStudents" :key="student.id" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div class="flex items-center gap-4">
                <div class="grid h-14 w-14 place-items-center rounded-full bg-amber-100 text-2xl">👦</div>
                <div>
                  <p class="text-2xl font-black text-slate-900">{{ student.name }}</p>
                  <p class="text-sm text-slate-500">{{ student.level }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700" @click="loadStudentDetail(student.id)">Aksi</button>
              </div>
            </div>
            <p v-if="!filteredStudents.length" class="px-6 py-8 text-sm text-slate-500">Tidak ada data siswa yang cocok dengan pencarian.</p>
          </div>

          <div v-else class="mt-6 space-y-6">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                  <div class="grid h-14 w-14 place-items-center rounded-full bg-amber-100 text-3xl">👩‍🎓</div>
                  <div>
                    <h3 class="text-2xl font-black text-slate-900">{{ selectedStudentDetail.name }}</h3>
                    <p class="text-sm text-slate-500">{{ selectedStudentDetail.current_level?.name ?? 'Pemula' }}</p>
                  </div>
                </div>
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600" @click="selectedStudentDetail = null">Kembali</button>
              </div>

              <div class="mt-5 flex flex-wrap gap-2">
                <button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="selectedTab === 'basic' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600'" @click="selectedTab = 'basic'">Informasi Dasar</button>
                <button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="selectedTab === 'progress' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600'" @click="selectedTab = 'progress'">Progress</button>
                <button type="button" class="rounded-xl px-4 py-2 text-sm font-bold" :class="selectedTab === 'activity' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600'" @click="selectedTab = 'activity'">Aktivitas</button>
              </div>
            </div>

            <div v-if="selectedTab === 'basic'" class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h4 class="text-xl font-black">Detail Siswa</h4>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Nama</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.name }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Level</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.current_level?.name ?? 'Pemula' }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Tempat Lahir</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.tempat_lahir || '-' }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Tanggal Lahir</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.tanggal_lahir ? new Date(selectedStudentDetail.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Wali / Orang Tua</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.nama_orang_tua_wali || '-' }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Email Wali</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.pendamping_email || '-' }}</p></div>
                  <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">No. Telepon</p><p class="mt-2 font-black text-slate-800">{{ selectedStudentDetail.pendamping_phone || '-' }}</p></div>
                </div>
              </div>

              <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                  <h4 class="text-xl font-black">Ringkasan</h4>
                  <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Progress</span><strong class="text-sky-700">{{ selectedStudentDetail.summary?.progress ?? 0 }}%</strong></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Rata-rata skor</span><strong class="text-violet-700">{{ selectedStudentDetail.summary?.average_score ?? 0 }}</strong></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Materi dibuka</span><strong class="text-emerald-700">{{ selectedStudentDetail.summary?.materials_accessed ?? 0 }}</strong></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Kuis selesai</span><strong class="text-amber-700">{{ selectedStudentDetail.summary?.quizzes_completed ?? 0 }}</strong></div>
                  </div>
                </div>
                <div class="rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200">
                  <h4 class="text-xl font-black">Catatan</h4>
                  <p class="mt-3 text-sm text-slate-600">Siswa ini sudah aktif dalam materi dan kuis. Pertahankan konsistensi belajar agar progres terus naik.</p>
                </div>
              </div>
            </div>

            <div v-else-if="selectedTab === 'progress'" class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h4 class="text-xl font-black">Progress Pembelajaran</h4>
                <div class="mt-5 space-y-4">
                  <div v-for="subject in selectedStudentDetail.progress_by_subject || []" :key="subject.slug" class="rounded-xl bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-4">
                      <div>
                        <p class="font-black text-slate-800">{{ subject.name }}</p>
                        <p class="text-xs text-slate-500">{{ subject.completed_materials || 0 }}/{{ subject.total_materials || 0 }} materi selesai</p>
                      </div>
                      <span class="font-black text-sky-700">{{ subject.progress }}%</span>
                    </div>
                    <div class="mt-3 h-2.5 rounded-full bg-slate-200">
                      <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${Math.min(subject.progress || 0, 100)}%` }"></div>
                    </div>
                  </div>
                  <p v-if="!selectedStudentDetail.progress_by_subject?.length" class="text-sm text-slate-500">Belum ada data progress per mata pelajaran.</p>
                </div>
              </div>

              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h4 class="text-xl font-black">Keterangan Progress</h4>
                <ul class="mt-5 space-y-3 text-sm text-slate-600">
                  <li class="rounded-xl bg-slate-50 p-3">Progress siswa: <strong class="text-sky-700">{{ selectedStudentDetail.summary?.progress ?? 0 }}%</strong></li>
                  <li class="rounded-xl bg-slate-50 p-3">Materi yang dibuka: <strong class="text-emerald-700">{{ selectedStudentDetail.summary?.materials_accessed ?? 0 }}</strong></li>
                  <li class="rounded-xl bg-slate-50 p-3">Kuis yang selesai: <strong class="text-violet-700">{{ selectedStudentDetail.summary?.quizzes_completed ?? 0 }}</strong></li>
                  <li class="rounded-xl bg-slate-50 p-3">Rata-rata skor: <strong class="text-amber-700">{{ selectedStudentDetail.summary?.average_score ?? 0 }}</strong></li>
                </ul>
              </div>
            </div>

            <div v-else-if="selectedTab === 'activity'" class="grid gap-6 xl:grid-cols-3">
              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-1">
                <h4 class="text-xl font-black">Aktivitas</h4>
                <div class="mt-5 space-y-3">
                  <div v-for="activity in selectedStudentDetail.activities || []" :key="`${activity.type}-${activity.label}-${activity.time}`" class="flex items-start gap-3 rounded-xl bg-slate-50 p-4">
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-sky-100 text-sm font-black text-sky-700">●</div>
                    <div class="min-w-0 flex-1">
                      <p class="font-black text-slate-800">{{ activity.type }}</p>
                      <p class="text-sm text-slate-600">{{ activity.label }}</p>
                    </div>
                    <div class="text-right text-[11px] font-bold text-slate-500">
                      <p v-if="activity.score !== undefined" class="text-violet-700">{{ activity.score }}</p>
                      <p>{{ new Date(activity.time).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                    </div>
                  </div>
                  <p v-if="!selectedStudentDetail.activities?.length" class="text-sm text-slate-500">Belum ada aktivitas siswa.</p>
                </div>
              </div>

              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-1">
                <h4 class="text-xl font-black">Nilai</h4>
                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                  <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                      <tr>
                        <th class="px-3 py-2 font-black">Judul</th>
                        <th class="px-3 py-2 font-black">Nilai</th>
                        <th class="px-3 py-2 font-black">Tanggal</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="score in selectedStudentDetail.scores || []" :key="`${score.title}-${score.completed_at}`" class="border-t border-slate-200">
                        <td class="px-3 py-2 font-bold text-slate-700">{{ score.title }}</td>
                        <td class="px-3 py-2 font-black" :class="(score.score ?? 0) >= 70 ? 'text-emerald-600' : 'text-amber-600'">{{ score.score }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ new Date(score.completed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-if="!selectedStudentDetail.scores?.length" class="mt-4 text-sm text-slate-500">Belum ada skor kuis.</p>
              </div>

              <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 xl:col-span-1">
                <h4 class="text-xl font-black">Riwayat Pembelajaran</h4>
                <div class="mt-5 space-y-3">
                  <div v-for="item in selectedStudentDetail.history || []" :key="`${item.type}-${item.label}-${item.time}`" class="rounded-xl bg-slate-50 p-3">
                    <p class="font-black text-slate-800">{{ item.type }}</p>
                    <p class="text-sm text-slate-600">{{ item.label }}</p>
                    <p class="mt-2 text-[11px] font-bold text-slate-500">{{ new Date(item.time).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                  </div>
                  <p v-if="!selectedStudentDetail.history?.length" class="text-sm text-slate-500">Belum ada riwayat pembelajaran.</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section v-if="guruView === 'progress'" class="mt-7"><div class="mb-6"><p class="text-xs font-black uppercase tracking-[0.2em] text-violet-600">Analytics</p><h2 class="mt-2 text-3xl font-black">Progress Pembelajaran</h2><p class="mt-1 text-slate-500">Data diambil dari akses materi dan hasil kuis siswa.</p></div><section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h3 class="text-xl font-black">Performa Kelas per Mata Pelajaran</h3><div class="mt-7 flex h-56 items-end justify-around gap-4 border-b border-slate-200 px-4"> <div v-for="subject in guruProgress?.subject_performance" :key="subject.name" class="flex h-full flex-1 flex-col items-center justify-end"><div class="w-full max-w-16 rounded-t-xl bg-violet-500" :style="{ height: `${Math.max(subject.progress, 4)}%` }" :title="`${subject.progress}%`"></div><p class="mt-3 text-center text-xs font-bold text-slate-600">{{ subject.name }}</p><p class="text-xs text-violet-600">{{ subject.progress }}%</p></div><p v-if="!guruProgress?.subject_performance?.length" class="self-center text-sm text-slate-500">Belum ada aktivitas materi.</p></div></section><div class="mt-6 grid gap-6 lg:grid-cols-2"><section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h3 class="text-xl font-black">Distribusi Status Siswa</h3><div class="mt-6 space-y-4"><div class="flex items-center justify-between"><span class="font-bold text-emerald-600">● On Track</span><strong>{{ guruProgress?.status.on_track ?? 0 }}</strong></div><div class="flex items-center justify-between"><span class="font-bold text-amber-500">● Needs Help</span><strong>{{ guruProgress?.status.needs_help ?? 0 }}</strong></div><div class="flex items-center justify-between"><span class="font-bold text-rose-500">● Inactive</span><strong>{{ guruProgress?.status.inactive ?? 0 }}</strong></div></div></section><section class="rounded-2xl bg-amber-50 p-6 ring-1 ring-amber-200"><h3 class="text-xl font-black">🔔 Perhatian</h3><p class="mt-3 text-sm leading-6 text-slate-600">Siswa berstatus <strong>Needs Help</strong> atau <strong>Inactive</strong> perlu dipantau dan diberikan pendampingan tambahan.</p></section></div><section class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h3 class="text-xl font-black">Detail Progress Siswa</h3><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead><tr class="border-b text-slate-500"><th class="p-3">Siswa</th><th class="p-3">Level</th><th class="p-3">Progress</th><th class="p-3">Nilai Rata-rata</th><th class="p-3">Materi</th><th class="p-3">Kuis</th></tr></thead><tbody><tr v-for="student in guruProgress?.students" :key="student.id" class="border-b last:border-0"><td class="p-3 font-bold">{{ student.name }}</td><td class="p-3">{{ student.level }}</td><td class="p-3"><div class="flex items-center gap-2"><div class="h-2 w-24 rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-400" :style="{ width: `${student.progress}%` }"></div></div>{{ student.progress }}%</div></td><td class="p-3">{{ student.average_score }}</td><td class="p-3">{{ student.materials_accessed }}</td><td class="p-3">{{ student.quizzes_completed }}</td></tr></tbody></table></div></section></section>
        <section v-if="guruView === 'materials'" class="mt-7"><div class="mb-6"><p class="text-xs font-black uppercase tracking-[0.2em] text-violet-600">Kelola Materi</p><h2 class="mt-2 text-3xl font-black">Materi Pembelajaran</h2><p class="mt-1 text-slate-500">Unggah materi PDF, PPT, atau link YouTube untuk pendamping siswa.</p></div><div class="grid gap-6 lg:grid-cols-2"><section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><div class="flex items-center justify-between"><h2 class="text-lg font-black">Tambah Materi Baru</h2><span class="text-2xl">📚</span></div><form class="mt-4 space-y-3" @submit.prevent="uploadMaterial"><input v-model="materialForm.title" required placeholder="Judul materi" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-blue-500" /><div class="grid gap-3 sm:grid-cols-2"><select v-model="materialForm.category_id" required class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Pilih kategori</option><option v-for="category in guruDashboard?.categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><select v-model="materialForm.type" class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="pdf">PDF</option><option value="ppt">PPT / PPTX</option><option value="youtube">Link YouTube</option></select></div><input v-if="materialForm.type !== 'youtube'" type="file" required accept=".pdf,.ppt,.pptx,application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation" class="w-full rounded-xl border border-dashed border-slate-300 p-3 text-sm" @change="setMaterialFile" /><input v-else v-model="materialForm.youtube_url" required type="url" placeholder="https://www.youtube.com/watch?v=..." class="min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" /><textarea v-model="materialForm.description" rows="2" placeholder="Deskripsi singkat (opsional)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea><button class="min-h-11 w-full rounded-xl bg-violet-600 px-4 font-bold text-white disabled:opacity-60" :disabled="isUploadingMaterial">{{ isUploadingMaterial ? 'Mengunggah...' : 'Simpan Materi' }}</button><p v-if="materialMessage" class="rounded-xl p-3 text-sm font-semibold" :class="materialError ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-700'">{{ materialMessage }}</p></form></section><section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h2 class="text-lg font-black">Materi Terunggah</h2><div class="mt-4 space-y-3"><div v-for="material in guruMaterials" :key="material.id" class="flex items-center justify-between rounded-xl bg-slate-50 p-3"><div class="min-w-0"><p class="truncate font-bold">{{ material.title }}</p><p class="text-xs text-slate-500">{{ material.category }} · {{ material.type.toUpperCase() }}</p></div><a :href="material.url" target="_blank" rel="noreferrer" class="ml-3 text-sm font-bold text-blue-600">Buka</a></div><p v-if="!guruMaterials.length" class="text-sm text-slate-500">Belum ada materi terunggah.</p></div></section></div></section>
        <section v-if="guruView === 'materials'" class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><h2 class="text-lg font-black">Buat Kuis dari Materi</h2><p class="mt-1 text-sm text-slate-500">Kuis yang disimpan akan muncul di halaman Kuis pendamping.</p><form class="mt-4 space-y-3" @submit.prevent="saveQuiz"><div class="grid gap-3 sm:grid-cols-2"><select v-model="quizForm.material_id" required class="min-h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Pilih materi</option><option v-for="material in guruMaterials" :key="material.id" :value="material.id">{{ material.title }}</option></select><input v-model="quizForm.title" required placeholder="Judul kuis" class="min-h-11 rounded-xl border border-slate-200 px-3 text-sm" /></div><textarea v-model="quizForm.description" placeholder="Deskripsi kuis" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea><div v-for="(question, questionIndex) in quizForm.questions" :key="questionIndex" class="rounded-xl bg-slate-50 p-4"><p class="font-black">Soal {{ questionIndex + 1 }}</p><input v-model="question.question_text" required placeholder="Tulis pertanyaan" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" /><div v-for="(answer, answerIndex) in question.answers" :key="answerIndex" class="mt-2 flex gap-2"><input v-model="answer.answer_text" required placeholder="Pilihan jawaban" class="min-h-10 flex-1 rounded-xl border border-slate-200 px-3 text-sm" /><label class="flex items-center gap-1 text-xs font-bold text-emerald-700"><input v-model="answer.is_correct" type="radio" :name="`correct-${questionIndex}`" :value="true" /> Benar</label></div><button type="button" class="mt-2 text-xs font-bold text-blue-600" @click="addQuizAnswer(question)">+ Tambah pilihan</button></div><div class="flex flex-wrap gap-3"><button type="button" class="rounded-xl bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700" @click="addQuizQuestion">+ Tambah soal</button><button class="rounded-xl bg-violet-600 px-5 py-2 text-sm font-bold text-white" :disabled="isSavingQuiz">{{ isSavingQuiz ? 'Menyimpan...' : 'Simpan Kuis' }}</button></div><p v-if="quizMessage" class="rounded-xl p-3 text-sm font-semibold" :class="quizError ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-700'">{{ quizMessage }}</p></form><div class="mt-5 border-t pt-4"><p class="text-sm font-black">Kuis tersimpan: {{ guruQuizzes.length }}</p><p v-for="quiz in guruQuizzes" :key="quiz.id" class="mt-2 text-sm text-slate-600">{{ quiz.title }} · {{ quiz.questions.length }} soal</p></div></section>
      </div></section>
    </div>
  </main>

  <main v-else class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white px-6 py-5 sm:px-10">
      <div class="mx-auto flex max-w-6xl items-center justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Smart Learning</p>
          <h1 class="mt-1 text-2xl font-black">Admin Dashboard</h1>
          <p class="text-sm text-slate-500">Selamat datang, {{ user.name }}.</p>
        </div>
        <div class="flex items-center gap-3">
          <button class="rounded-xl px-3 py-2 text-sm font-bold" :class="currentView === 'dashboard' ? 'bg-teal-50 text-teal-700' : 'text-slate-500'" @click="go('/admin/dashboard')">Dashboard</button>
          <button class="rounded-xl px-3 py-2 text-sm font-bold" :class="currentView === 'tokens' ? 'bg-teal-50 text-teal-700' : 'text-slate-500'" @click="go('/admin/tokens')">Token</button>
          <button class="rounded-xl px-3 py-2 text-sm font-bold" :class="currentView === 'users' ? 'bg-teal-50 text-teal-700' : 'text-slate-500'" @click="go('/admin/users')">Pengguna</button>
          <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 hover:border-teal-600 hover:text-teal-700" @click="logout">Keluar</button>
        </div>
      </div>
    </header>

    <AdminUserManagement v-if="currentView === 'users'" :api="api" :current-user-id="user.id" />

    <section v-else-if="currentView === 'dashboard'" class="mx-auto max-w-6xl space-y-6 px-6 py-8 sm:px-10">
      <div class="rounded-2xl bg-gradient-to-r from-violet-600 to-violet-500 p-6 text-white shadow-lg shadow-violet-200">
        <p class="text-2xl font-black">Sistem Berjalan Normal</p>
        <p class="mt-1 font-semibold text-violet-100">Semua layanan aktif</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article v-for="card in [
          { label: 'Total Pengguna', value: stats.total_pengguna, color: 'text-sky-500' },
          { label: 'Pendamping', value: stats.pendamping, color: 'text-emerald-500' },
          { label: 'Guru', value: stats.guru, color: 'text-violet-500' },
          { label: 'Token Aktif', value: stats.token_aktif, color: 'text-amber-500' },
        ]" :key="card.label" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
          <p class="text-sm font-bold text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-black" :class="card.color">{{ card.value }}</p>
        </article>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
          <h2 class="text-lg font-black">Distribusi Pengguna</h2>
          <div v-for="item in distribution" :key="item.label" class="mt-5">
            <div class="flex justify-between text-sm font-bold"><span>{{ item.label }}</span><span>{{ item.value }} orang ({{ item.percentage }}%)</span></div>
            <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full" :class="item.color" :style="{ width: `${item.percentage}%` }"></div></div>
          </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
          <h2 class="text-lg font-black">Status Sistem</h2>
          <div class="mt-5 space-y-4 text-sm">
            <div class="flex items-center justify-between"><span class="text-slate-500">Server API</span><strong class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Online</strong></div>
            <div class="flex items-center justify-between"><span class="text-slate-500">Database</span><strong class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Optimal</strong></div>
            <div class="flex items-center justify-between"><span class="text-slate-500">Layanan AI</span><strong class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">Siap</strong></div>
          </div>
        </section>
      </div>
    </section>

    <section v-else class="mx-auto max-w-6xl space-y-6 px-6 py-8 sm:px-10">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Manajemen akses</p>
        <h2 class="mt-2 text-3xl font-black">Buat token siswa</h2>
        <p class="mt-2 text-slate-500">Daftarkan siswa lalu buat token akses untuk pendamping.</p>
      </div>

      <form class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8" @submit.prevent="generateToken">
        <div class="grid gap-5 md:grid-cols-2">
          <div>
            <label class="text-sm font-bold" for="student-name">Nama Siswa</label>
            <input id="student-name" v-model="tokenForm.nama" required class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
          <div>
            <label class="text-sm font-bold" for="birth-place">Tempat Lahir</label>
            <input id="birth-place" v-model="tokenForm.tempat_lahir" required class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
          <div>
            <label class="text-sm font-bold" for="birth-date">Tanggal Lahir</label>
            <input id="birth-date" v-model="tokenForm.tanggal_lahir" required type="date" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
          <div>
            <label class="text-sm font-bold" for="age">Usia</label>
            <input id="age" :value="studentAge" readonly placeholder="Otomatis" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-slate-500" />
          </div>
          <div>
            <label class="text-sm font-bold" for="gender">Jenis Kelamin</label>
            <select id="gender" v-model="tokenForm.gender" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 outline-none focus:border-teal-600">
              <option value="">Pilih</option>
              <option value="laki-laki">Laki-laki</option>
              <option value="perempuan">Perempuan</option>
            </select>
          </div>
          <div>
            <label class="text-sm font-bold" for="disability">Jenis Disabilitas / Inklusif</label>
            <select id="disability" v-model="tokenForm.disability" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 bg-white px-4 outline-none focus:border-teal-600">
              <option value="">Pilih</option>
              <option value="down-syndrome">Down Syndrome</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-3">
          <div>
            <label class="text-sm font-bold" for="guardian-name">Nama Orang Tua / Wali</label>
            <input id="guardian-name" v-model="tokenForm.nama_orang_tua_wali" required class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
          <div>
            <label class="text-sm font-bold" for="guardian-email">Email Orang Tua / Wali</label>
            <input id="guardian-email" v-model="tokenForm.pendamping_email" type="email" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
          <div>
            <label class="text-sm font-bold" for="guardian-phone">No. Telp Orang Tua / Wali</label>
            <input id="guardian-phone" v-model="tokenForm.pendamping_phone" type="tel" class="mt-2 min-h-12 w-full rounded-xl border border-slate-200 px-4 outline-none focus:border-teal-600" />
          </div>
        </div>

        <button class="mt-6 min-h-12 w-full rounded-xl bg-teal-600 px-6 font-bold text-white transition hover:bg-teal-700 disabled:cursor-wait disabled:opacity-60" :disabled="isGeneratingToken">
          {{ isGeneratingToken ? 'Membuat token...' : 'Generate Token' }}
        </button>
        <p v-if="tokenMessage" class="mt-4 rounded-xl p-4 text-sm font-semibold" :class="tokenError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'">{{ tokenMessage }}</p>
        <p v-if="generatedToken" class="mt-3 rounded-xl border-2 border-dashed border-teal-300 bg-teal-50 p-4 text-center font-black tracking-widest text-teal-800">{{ generatedToken }}</p>
      </form>

      <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-xl font-black">Permintaan Token</h2>
        <p class="mt-1 text-sm text-slate-500">Token baru dibuat langsung setelah data siswa berhasil disimpan.</p>
      </div>
    </section>
  </main>
</template>
