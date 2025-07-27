export interface User {
  id: number;
  name: string;
  email: string;
  type: 'admin' | 'assistant' | 'professional';
  phone?: string;
  specialty?: string;
  created_at: string;
  updated_at: string;
}

export interface Patient {
  id: number;
  name: string;
  birth_date: string;
  category: string;
  gender: 'M' | 'F' | 'Other';
  phone?: string;
  email?: string;
  address?: string;
  responsible_id: number;
  insurance_plan_id?: number;
  created_at: string;
  updated_at: string;
}

export interface MedicalRecord {
  id: number;
  patient_id: number;
  professional_id: number;
  type: 'document' | 'report' | 'evaluation' | 'other';
  title: string;
  description?: string;
  file_path: string;
  file_name: string;
  created_at: string;
}

export interface Appointment {
  id: number;
  patient_id: number;
  professional_id: number;
  therapy_type_id: number;
  date: string;
  time: string;
  frequency: 'single' | 'weekly' | 'biweekly' | 'monthly';
  status: 'scheduled' | 'completed' | 'cancelled';
  notes?: string;
  created_at: string;
}

export interface Evolution {
  id: number;
  patient_id: number;
  professional_id: number;
  date: string;
  description: string;
  observations?: string;
  created_at: string;
  updated_at: string;
}

export interface InsurancePlan {
  id: number;
  name: string;
  psychology_value: number;
  physiotherapy_value: number;
  occupational_therapy_value: number;
  speech_therapy_value: number;
  phone?: string;
  email?: string;
  start_date: string;
  created_at: string;
  updated_at: string;
}

export interface TherapyType {
  id: number;
  name: string;
  description?: string;
  specialty: string;
  created_at: string;
  updated_at: string;
}

export interface Companion {
  id: number;
  name: string;
  phone?: string;
  email?: string;
  patient_id?: number;
  created_at: string;
  updated_at: string;
}

export interface Community {
  id: number;
  patient_id: number;
  professional_id: number;
  message: string;
  created_at: string;
  professional?: User;
}

export interface PDFConfig {
  id: number;
  admin_id: number;
  clinic_name: string;
  clinic_address: string;
  logo_path?: string;
  header_text?: string;
  footer_text?: string;
  font_family: string;
  font_size: number;
  primary_color: string;
  show_description: boolean;
  show_observations: boolean;
  show_professional: boolean;
  show_date: boolean;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  success: boolean;
}