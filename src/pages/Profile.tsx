import React, { useState } from 'react';
import { Save, Upload, Eye, EyeOff } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Textarea';
import { Select } from '@/components/ui/Select';
import { useAuth } from '@/contexts/AuthContext';
import { usersApi, pdfConfigApi } from '@/lib/api';

interface ProfileFormData {
  name: string;
  email: string;
  phone: string;
  specialty: string;
  password: string;
}

interface PDFConfigData {
  clinic_name: string;
  clinic_address: string;
  header_text: string;
  footer_text: string;
  font_family: string;
  font_size: number;
  primary_color: string;
  show_description: boolean;
  show_observations: boolean;
  show_professional: boolean;
  show_date: boolean;
}

export function Profile() {
  const { user, updateUser } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [profileData, setProfileData] = useState<ProfileFormData>({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    specialty: user?.specialty || '',
    password: '',
  });
  const [pdfConfig, setPdfConfig] = useState<PDFConfigData>({
    clinic_name: '',
    clinic_address: '',
    header_text: '',
    footer_text: '',
    font_family: 'Arial',
    font_size: 12,
    primary_color: '#2563EB',
    show_description: true,
    show_observations: true,
    show_professional: true,
    show_date: true,
  });

  React.useEffect(() => {
    if (user?.type === 'admin') {
      fetchPDFConfig();
    }
  }, [user]);

  const fetchPDFConfig = async () => {
    try {
      const response = await pdfConfigApi.get();
      if (response.data.data) {
        setPdfConfig(response.data.data);
      }
    } catch (error) {
      console.error('Error fetching PDF config:', error);
    }
  };

  const handleProfileSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const updateData: any = {
        name: profileData.name,
        email: profileData.email,
        phone: profileData.phone,
        specialty: profileData.specialty,
      };

      if (profileData.password.trim()) {
        updateData.password = profileData.password;
      }

      await usersApi.update(user!.id, updateData);
      updateUser(updateData);
      setProfileData({ ...profileData, password: '' });
      alert('Perfil atualizado com sucesso!');
    } catch (error) {
      console.error('Error updating profile:', error);
      alert('Erro ao atualizar perfil. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const handlePDFConfigSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      await pdfConfigApi.update(pdfConfig);
      alert('Configurações de PDF atualizadas com sucesso!');
    } catch (error) {
      console.error('Error updating PDF config:', error);
      alert('Erro ao atualizar configurações. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const handleLogoUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('logo', file);

    try {
      await pdfConfigApi.uploadLogo(formData);
      alert('Logo atualizado com sucesso!');
      await fetchPDFConfig();
    } catch (error) {
      console.error('Error uploading logo:', error);
      alert('Erro ao fazer upload do logo. Tente novamente.');
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Perfil</h1>
        <p className="text-gray-600">Gerencie suas informações pessoais e configurações</p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          <button
            onClick={() => setActiveTab('profile')}
            className={`py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'profile'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Dados Pessoais
          </button>
          {user?.type === 'admin' && (
            <button
              onClick={() => setActiveTab('pdf')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'pdf'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Configurações de PDF
            </button>
          )}
        </nav>
      </div>

      {/* Profile Tab */}
      {activeTab === 'profile' && (
        <Card>
          <CardHeader>
            <CardTitle>Informações Pessoais</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleProfileSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Input
                  label="Nome *"
                  value={profileData.name}
                  onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                  required
                />
                <Input
                  label="E-mail *"
                  type="email"
                  value={profileData.email}
                  onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                  required
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Input
                  label="Telefone"
                  value={profileData.phone}
                  onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                  placeholder="(00) 00000-0000"
                />
                <Input
                  label="Especialidade"
                  value={profileData.specialty}
                  onChange={(e) => setProfileData({ ...profileData, specialty: e.target.value })}
                  placeholder="Ex: Psicologia, Fisioterapia..."
                />
              </div>

              <div className="relative">
                <Input
                  label="Nova Senha (deixe vazio para manter a atual)"
                  type={showPassword ? 'text' : 'password'}
                  value={profileData.password}
                  onChange={(e) => setProfileData({ ...profileData, password: e.target.value })}
                />
                <button
                  type="button"
                  className="absolute right-3 top-8 text-gray-400 hover:text-gray-600"
                  onClick={() => setShowPassword(!showPassword)}
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>

              <div className="flex justify-end">
                <Button type="submit" loading={loading}>
                  <Save className="h-4 w-4 mr-2" />
                  Salvar Alterações
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* PDF Configuration Tab */}
      {activeTab === 'pdf' && user?.type === 'admin' && (
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Configurações de Relatório PDF</CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handlePDFConfigSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <Input
                    label="Nome da Clínica *"
                    value={pdfConfig.clinic_name}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, clinic_name: e.target.value })}
                    required
                  />
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Logo da Clínica
                    </label>
                    <Input
                      type="file"
                      onChange={handleLogoUpload}
                      accept="image/*"
                    />
                  </div>
                </div>

                <Textarea
                  label="Endereço da Clínica"
                  value={pdfConfig.clinic_address}
                  onChange={(e) => setPdfConfig({ ...pdfConfig, clinic_address: e.target.value })}
                  rows={3}
                />

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <Textarea
                    label="Texto do Cabeçalho"
                    value={pdfConfig.header_text}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, header_text: e.target.value })}
                    rows={3}
                  />
                  <Textarea
                    label="Texto do Rodapé"
                    value={pdfConfig.footer_text}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, footer_text: e.target.value })}
                    rows={3}
                  />
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <Select
                    label="Fonte"
                    value={pdfConfig.font_family}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, font_family: e.target.value })}
                    options={[
                      { value: 'Arial', label: 'Arial' },
                      { value: 'Times New Roman', label: 'Times New Roman' },
                      { value: 'Helvetica', label: 'Helvetica' },
                      { value: 'Calibri', label: 'Calibri' },
                    ]}
                  />
                  <Input
                    label="Tamanho da Fonte"
                    type="number"
                    min="8"
                    max="16"
                    value={pdfConfig.font_size}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, font_size: parseInt(e.target.value) })}
                  />
                  <Input
                    label="Cor Primária"
                    type="color"
                    value={pdfConfig.primary_color}
                    onChange={(e) => setPdfConfig({ ...pdfConfig, primary_color: e.target.value })}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-3">
                    Campos a Exibir no Relatório
                  </label>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={pdfConfig.show_description}
                        onChange={(e) => setPdfConfig({ ...pdfConfig, show_description: e.target.checked })}
                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span className="ml-2 text-sm text-gray-700">Descrição</span>
                    </label>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={pdfConfig.show_observations}
                        onChange={(e) => setPdfConfig({ ...pdfConfig, show_observations: e.target.checked })}
                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span className="ml-2 text-sm text-gray-700">Observações</span>
                    </label>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={pdfConfig.show_professional}
                        onChange={(e) => setPdfConfig({ ...pdfConfig, show_professional: e.target.checked })}
                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span className="ml-2 text-sm text-gray-700">Profissional</span>
                    </label>
                    <label className="flex items-center">
                      <input
                        type="checkbox"
                        checked={pdfConfig.show_date}
                        onChange={(e) => setPdfConfig({ ...pdfConfig, show_date: e.target.checked })}
                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span className="ml-2 text-sm text-gray-700">Data</span>
                    </label>
                  </div>
                </div>

                <div className="flex justify-end">
                  <Button type="submit" loading={loading}>
                    <Save className="h-4 w-4 mr-2" />
                    Salvar Configurações
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}