import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format, parseISO } from 'date-fns';
import { ptBR } from 'date-fns/locale';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(date: string | Date, pattern = 'dd/MM/yyyy') {
  const dateObj = typeof date === 'string' ? parseISO(date) : date;
  return format(dateObj, pattern, { locale: ptBR });
}

export function formatDateTime(date: string | Date, pattern = 'dd/MM/yyyy HH:mm') {
  const dateObj = typeof date === 'string' ? parseISO(date) : date;
  return format(dateObj, pattern, { locale: ptBR });
}

export function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(value);
}

export function getInitials(name: string) {
  return name
    .split(' ')
    .map(word => word.charAt(0).toUpperCase())
    .slice(0, 2)
    .join('');
}

export function getUserTypeLabel(type: string) {
  const types = {
    admin: 'Administrador',
    assistant: 'Assistente',
    professional: 'Profissional',
  };
  return types[type as keyof typeof types] || type;
}

export function getGenderLabel(gender: string) {
  const genders = {
    M: 'Masculino',
    F: 'Feminino',
    Other: 'Outro',
  };
  return genders[gender as keyof typeof genders] || gender;
}

export function getFrequencyLabel(frequency: string) {
  const frequencies = {
    single: 'Única',
    weekly: 'Semanal',
    biweekly: 'Quinzenal',
    monthly: 'Mensal',
  };
  return frequencies[frequency as keyof typeof frequencies] || frequency;
}

export function getStatusLabel(status: string) {
  const statuses = {
    scheduled: 'Agendado',
    completed: 'Concluído',
    cancelled: 'Cancelado',
  };
  return statuses[status as keyof typeof statuses] || status;
}

export function getStatusColor(status: string) {
  const colors = {
    scheduled: 'text-blue-600 bg-blue-100',
    completed: 'text-green-600 bg-green-100',
    cancelled: 'text-red-600 bg-red-100',
  };
  return colors[status as keyof typeof colors] || 'text-gray-600 bg-gray-100';
}

export function canAccess(userType: string, requiredPermissions: string[]): boolean {
  const permissions = {
    admin: ['admin', 'assistant', 'professional'],
    assistant: ['assistant', 'professional'],
    professional: ['professional'],
  };
  
  const userPermissions = permissions[userType as keyof typeof permissions] || [];
  return requiredPermissions.some(permission => userPermissions.includes(permission));
}

export function validateEmail(email: string): boolean {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

export function validatePhone(phone: string): boolean {
  const phoneRegex = /^\(\d{2}\)\s\d{4,5}-\d{4}$/;
  return phoneRegex.test(phone);
}

export function formatPhone(phone: string): string {
  const numbers = phone.replace(/\D/g, '');
  if (numbers.length === 11) {
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 7)}-${numbers.slice(7)}`;
  } else if (numbers.length === 10) {
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 6)}-${numbers.slice(6)}`;
  }
  return phone;
}

export function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}