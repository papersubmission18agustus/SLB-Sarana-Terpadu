<script setup>
import { onMounted, ref } from 'vue'

const props = defineProps({
  api: { type: Object, required: true },
  currentUserId: { type: [Number, String], required: true },
})

const users = ref([])
const search = ref('')
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const validationErrors = ref({})
const modal = ref(null)
const selectedUser = ref(null)
const form = ref(emptyForm())

function emptyForm() {
  return { id: null, name: '', username: '', email: '', password: '', role: 'guru' }
}

function openCreate() {
  selectedUser.value = null
  form.value = emptyForm()
  validationErrors.value = {}
  errorMessage.value = ''
  modal.value = 'form'
}

async function openEdit(user) {
  selectedUser.value = user
  form.value = { ...user, password: '' }
  validationErrors.value = {}
  errorMessage.value = ''
  modal.value = 'form'
}

async function openDetail(user) {
  selectedUser.value = user
  modal.value = 'detail'
}

function closeModal() {
  modal.value = null
  selectedUser.value = null
}

function firstError(error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : (error.response?.data?.message ?? 'Terjadi kesalahan. Coba lagi.')
}

async function loadUsers() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await props.api.get('/api/admin/users', { params: { search: search.value || undefined } })
    users.value = response.data.data
  } catch (error) {
    errorMessage.value = firstError(error)
  } finally {
    isLoading.value = false
  }
}

async function saveUser() {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  validationErrors.value = {}
  try {
    const payload = { ...form.value }
    if (!payload.password) delete payload.password
    const response = form.value.id
      ? await props.api.put(`/api/admin/users/${form.value.id}`, payload)
      : await props.api.post('/api/admin/users', payload)
    successMessage.value = response.data.message
    closeModal()
    await loadUsers()
  } catch (error) {
    validationErrors.value = error.response?.data?.errors ?? {}
    errorMessage.value = firstError(error)
  } finally {
    isSaving.value = false
  }
}

async function deleteUser(user) {
  if (String(user.id) === String(props.currentUserId)) {
    errorMessage.value = 'Akun yang sedang digunakan tidak dapat dihapus.'
    return
  }
  if (!window.confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?')) return

  try {
    const response = await props.api.delete(`/api/admin/users/${user.id}`)
    successMessage.value = response.data.message
    await loadUsers()
  } catch (error) {
    errorMessage.value = firstError(error)
  }
}

function fieldError(field) {
  return validationErrors.value[field]?.[0]
}

onMounted(loadUsers)
</script>

<template>
  <section class="mx-auto max-w-6xl space-y-5 px-6 py-8 sm:px-10">
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Administrasi</p>
        <h2 class="mt-2 text-3xl font-black">Kelola Akun Pengguna</h2>
        <p class="mt-1 text-slate-500">Kelola akun Admin dan Guru yang dapat mengakses sistem.</p>
      </div>
      <button class="rounded-xl bg-teal-700 px-5 py-3 font-bold text-white hover:bg-teal-800" @click="openCreate">+ Tambah Pengguna</button>
    </header>

    <div v-if="successMessage" class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ successMessage }}</div>
    <div v-if="errorMessage" class="rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ errorMessage }}</div>

    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-lg font-black">Daftar Pengguna</h3>
        <div class="flex gap-2">
          <input v-model="search" class="min-h-10 rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-teal-600" placeholder="Cari nama, username, email" @keyup.enter="loadUsers" />
          <button class="rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600" @click="loadUsers">Cari</button>
        </div>
      </div>

      <div v-if="isLoading" class="py-12 text-center text-sm text-slate-500">Memuat data pengguna...</div>
      <div v-else-if="!users.length" class="py-12 text-center text-sm text-slate-500">Belum ada akun pengguna yang sesuai.</div>
      <div v-else class="mt-5 overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
          <thead><tr class="border-b text-xs uppercase tracking-wide text-slate-500"><th class="p-3">Nama</th><th class="p-3">Username</th><th class="p-3">Email</th><th class="p-3">Role</th><th class="p-3">Aksi</th></tr></thead>
          <tbody><tr v-for="user in users" :key="user.id" class="border-b last:border-0 hover:bg-slate-50"><td class="p-3 font-bold">{{ user.name }}</td><td class="p-3">{{ user.username }}</td><td class="p-3">{{ user.email || '-' }}</td><td class="p-3"><span class="rounded-full px-3 py-1 text-xs font-bold" :class="user.role === 'admin' ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-sky-700'">{{ user.role }}</span></td><td class="p-3"><div class="flex gap-2"><button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700" @click="openDetail(user)">Detail</button><button class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700" @click="openEdit(user)">Edit</button><button class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700" @click="deleteUser(user)">Hapus</button></div></td></tr></tbody>
        </table>
      </div>
    </section>

    <div v-if="modal" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-5" @click.self="closeModal">
      <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between"><h3 class="text-xl font-black">{{ modal === 'detail' ? 'Detail Akun Pengguna' : form.id ? 'Edit Akun Pengguna' : 'Tambah Pengguna' }}</h3><button class="text-2xl text-slate-400" @click="closeModal">&times;</button></div>
        <dl v-if="modal === 'detail'" class="mt-6 space-y-4 text-sm"><div><dt class="font-bold text-slate-500">Nama</dt><dd class="mt-1">{{ selectedUser.name }}</dd></div><div><dt class="font-bold text-slate-500">Username</dt><dd class="mt-1">{{ selectedUser.username }}</dd></div><div><dt class="font-bold text-slate-500">Email</dt><dd class="mt-1">{{ selectedUser.email || '-' }}</dd></div><div><dt class="font-bold text-slate-500">Role</dt><dd class="mt-1 uppercase">{{ selectedUser.role }}</dd></div><div><dt class="font-bold text-slate-500">Dibuat</dt><dd class="mt-1">{{ selectedUser.created_at }}</dd></div></dl>
        <form v-else class="mt-5 space-y-4" @submit.prevent="saveUser"><div><label class="text-sm font-bold">Nama</label><input v-model="form.name" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3" /><p v-if="fieldError('name')" class="mt-1 text-xs text-rose-600">{{ fieldError('name') }}</p></div><div><label class="text-sm font-bold">Username</label><input v-model="form.username" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3" /><p v-if="fieldError('username')" class="mt-1 text-xs text-rose-600">{{ fieldError('username') }}</p></div><div><label class="text-sm font-bold">Email</label><input v-model="form.email" type="email" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3" /><p v-if="fieldError('email')" class="mt-1 text-xs text-rose-600">{{ fieldError('email') }}</p></div><div><label class="text-sm font-bold">Password {{ form.id ? '(kosongkan jika tidak diubah)' : '' }}</label><input v-model="form.password" type="password" :required="!form.id" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3" /><p v-if="fieldError('password')" class="mt-1 text-xs text-rose-600">{{ fieldError('password') }}</p></div><div><label class="text-sm font-bold">Role</label><select v-model="form.role" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3"><option value="admin">Admin</option><option value="guru">Guru</option></select><p v-if="fieldError('role')" class="mt-1 text-xs text-rose-600">{{ fieldError('role') }}</p></div><div class="flex justify-end gap-3"><button type="button" class="rounded-xl border border-slate-200 px-4 py-2 font-bold text-slate-600" @click="closeModal">Batal</button><button class="rounded-xl bg-teal-700 px-5 py-2 font-bold text-white" :disabled="isSaving">{{ isSaving ? 'Menyimpan...' : 'Simpan' }}</button></div></form>
      </section>
    </div>
  </section>
</template>
