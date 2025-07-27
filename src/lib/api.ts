import axios from 'axios';
import { AuthResponse, ApiResponse } from '@/types';

const API_BASE_URL = 'http://localhost:8000/api';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle unauthorized responses
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth endpoints
export const authApi = {
  login: (email: string, password: string) =>
    api.post<AuthResponse>('/auth/login', { email, password }),
  
  logout: () => api.post('/auth/logout'),
  
  me: () => api.get<ApiResponse<any>>('/auth/me'),
};

// Users endpoints
export const usersApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/users'),
  getById: (id: number) => api.get<ApiResponse<any>>(`/users/${id}`),
  create: (data: any) => api.post<ApiResponse<any>>('/users', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/users/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/users/${id}`),
};

// Patients endpoints
export const patientsApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/patients'),
  getById: (id: number) => api.get<ApiResponse<any>>(`/patients/${id}`),
  create: (data: any) => api.post<ApiResponse<any>>('/patients', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/patients/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/patients/${id}`),
  getMedicalRecords: (id: number) => api.get<ApiResponse<any[]>>(`/patients/${id}/medical-records`),
  uploadMedicalRecord: (id: number, formData: FormData) => 
    api.post<ApiResponse<any>>(`/patients/${id}/medical-records`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
};

// Appointments endpoints
export const appointmentsApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/appointments'),
  getById: (id: number) => api.get<ApiResponse<any>>(`/appointments/${id}`),
  create: (data: any) => api.post<ApiResponse<any>>('/appointments', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/appointments/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/appointments/${id}`),
};

// Evolutions endpoints
export const evolutionsApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/evolutions'),
  getByPatient: (patientId: number) => api.get<ApiResponse<any[]>>(`/evolutions/patient/${patientId}`),
  create: (data: any) => api.post<ApiResponse<any>>('/evolutions', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/evolutions/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/evolutions/${id}`),
  exportPDF: (id: number) => api.get(`/evolutions/${id}/pdf`, { responseType: 'blob' }),
};

// Insurance plans endpoints
export const insurancePlansApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/insurance-plans'),
  create: (data: any) => api.post<ApiResponse<any>>('/insurance-plans', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/insurance-plans/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/insurance-plans/${id}`),
};

// Therapy types endpoints
export const therapyTypesApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/therapy-types'),
  create: (data: any) => api.post<ApiResponse<any>>('/therapy-types', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/therapy-types/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/therapy-types/${id}`),
};

// Companions endpoints
export const companionsApi = {
  getAll: () => api.get<ApiResponse<any[]>>('/companions'),
  create: (data: any) => api.post<ApiResponse<any>>('/companions', data),
  update: (id: number, data: any) => api.put<ApiResponse<any>>(`/companions/${id}`, data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/companions/${id}`),
};

// Community endpoints
export const communityApi = {
  getByPatient: (patientId: number) => api.get<ApiResponse<any[]>>(`/community/patient/${patientId}`),
  create: (data: any) => api.post<ApiResponse<any>>('/community', data),
  delete: (id: number) => api.delete<ApiResponse<any>>(`/community/${id}`),
};

// Dashboard endpoints
export const dashboardApi = {
  getStats: () => api.get<ApiResponse<any>>('/dashboard/stats'),
  getUpcomingAppointments: () => api.get<ApiResponse<any[]>>('/dashboard/upcoming-appointments'),
  getAlerts: () => api.get<ApiResponse<any[]>>('/dashboard/alerts'),
};

// PDF Config endpoints
export const pdfConfigApi = {
  get: () => api.get<ApiResponse<any>>('/pdf-config'),
  update: (data: any) => api.put<ApiResponse<any>>('/pdf-config', data),
  uploadLogo: (formData: FormData) => 
    api.post<ApiResponse<any>>('/pdf-config/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
};