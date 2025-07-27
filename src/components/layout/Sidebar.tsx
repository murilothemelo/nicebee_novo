import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { 
  Users, 
  UserCheck, 
  Calendar, 
  FileText, 
  MessageSquare, 
  CreditCard, 
  Settings, 
  Home,
  LogOut,
  Stethoscope,
  FileHeart,
  UserPlus,
  BarChart3
} from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { canAccess } from '@/lib/utils';
import { Button } from '@/components/ui/Button';

const navigationItems = [
  {
    name: 'Dashboard',
    href: '/',
    icon: Home,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Usuários',
    href: '/users',
    icon: Users,
    permissions: ['admin', 'assistant'],
  },
  {
    name: 'Pacientes',
    href: '/patients',
    icon: UserCheck,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Agenda',
    href: '/appointments',
    icon: Calendar,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Evoluções',
    href: '/evolutions',
    icon: FileText,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Comunidade',
    href: '/community',
    icon: MessageSquare,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Planos Médicos',
    href: '/insurance-plans',
    icon: CreditCard,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Tipos de Terapia',
    href: '/therapy-types',
    icon: Stethoscope,
    permissions: ['admin', 'assistant', 'professional'],
  },
  {
    name: 'Acompanhantes',
    href: '/companions',
    icon: UserPlus,
    permissions: ['admin', 'assistant', 'professional'],
  },
];

export function Sidebar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  if (!user) return null;

  return (
    <div className="flex flex-col h-full bg-white border-r border-gray-200">
      <div className="flex items-center px-6 py-4 border-b border-gray-200">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <FileHeart className="h-8 w-8 text-blue-600" />
          </div>
          <div className="ml-3">
            <h1 className="text-lg font-semibold text-gray-900">
              Clínica Multidisciplinar
            </h1>
            <p className="text-sm text-gray-500">
              Gestão Terapêutica
            </p>
          </div>
        </div>
      </div>

      <nav className="flex-1 px-4 py-4 space-y-1">
        {navigationItems.map((item) => {
          if (!canAccess(user.type, item.permissions)) {
            return null;
          }

          return (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                `group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors ${
                  isActive
                    ? 'bg-blue-100 text-blue-700'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                }`
              }
            >
              <item.icon
                className="mr-3 h-5 w-5 flex-shrink-0"
                aria-hidden="true"
              />
              {item.name}
            </NavLink>
          );
        })}
      </nav>

      <div className="border-t border-gray-200 p-4">
        <div className="flex items-center mb-3">
          <div className="flex-shrink-0">
            <div className="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
              <span className="text-sm font-medium text-white">
                {user.name.charAt(0).toUpperCase()}
              </span>
            </div>
          </div>
          <div className="ml-3">
            <p className="text-sm font-medium text-gray-900">{user.name}</p>
            <p className="text-xs text-gray-500">
              {user.type === 'admin' ? 'Administrador' : 
               user.type === 'assistant' ? 'Assistente' : 'Profissional'}
            </p>
          </div>
        </div>
        
        <div className="space-y-1">
          <NavLink
            to="/profile"
            className="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900"
          >
            <Settings className="mr-3 h-4 w-4" />
            Perfil
          </NavLink>
          
          <Button
            variant="ghost"
            size="sm"
            onClick={handleLogout}
            className="w-full justify-start p-2 h-auto font-medium text-gray-600 hover:text-gray-900"
          >
            <LogOut className="mr-3 h-4 w-4" />
            Sair
          </Button>
        </div>
      </div>
    </div>
  );
}