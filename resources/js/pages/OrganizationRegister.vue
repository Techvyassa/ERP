<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <i class="fas fa-industry text-white text-xl"></i>
          </div>
          <span class="text-xl font-semibold text-gray-900">Fabricate ERP</span>
        </div>
        <div class="flex items-center space-x-6">
          <button class="text-gray-600 hover:text-gray-900">
            <i class="far fa-bell text-xl"></i>
          </button>
          <button class="text-gray-600 hover:text-gray-900">
            <i class="far fa-question-circle text-xl"></i>
          </button>
          <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
            <i class="fas fa-user text-gray-600"></i>
          </div>
        </div>
      </div>
    </header>

    <div class="flex min-h-[calc(100vh-80px)]">
      <!-- Sidebar -->
      <aside class="w-64 bg-white border-r border-gray-200 p-6">
        <div class="mb-8">
          <h3 class="text-sm font-semibold text-gray-500 mb-4">SETUP PROGRESS</h3>
          <div class="space-y-4">
            <!-- Step 1: Account Login -->
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-green-600 text-sm"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">Account Login</div>
                <div class="text-xs text-green-600">Completed</div>
              </div>
            </div>

            <!-- Step 2: Organization Info -->
            <div class="flex items-center space-x-3 bg-blue-50 -mx-3 px-3 py-2 rounded-lg">
              <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-white text-sm"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">Organization Info</div>
                <div class="text-xs text-blue-600">Current Step</div>
              </div>
            </div>

            <!-- Step 3: Subscription -->
            <div class="flex items-center space-x-3 opacity-50">
              <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-credit-card text-gray-400 text-sm"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">Subscription</div>
                <div class="text-xs text-gray-500">Upcoming</div>
              </div>
            </div>

            <!-- Step 4: Final Setup -->
            <div class="flex items-center space-x-3 opacity-50">
              <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-flag-checkered text-gray-400 text-sm"></i>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">Final Setup</div>
                <div class="text-xs text-gray-500">Upcoming</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-8">
          <div class="flex justify-between text-xs text-gray-600 mb-2">
            <span>Wizard Progress</span>
            <span class="font-semibold">50%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" style="width: 50%"></div>
          </div>
          <div class="text-xs text-gray-500 mt-2">STEP 2 OF 4</div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
          <!-- Step Indicator -->
          <div class="mb-6">
            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
              STEP 2: ORGANIZATION REGISTRATION
            </span>
          </div>

          <!-- Title -->
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Your Organization Profile</h1>
          <p class="text-gray-600 mb-8">Please provide your company details to initialize your manufacturing enterprise workspace.</p>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <!-- Organization Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-building"></i>
                  </span>
                  <input 
                    v-model="form.org_name" 
                    @input="generateSlug"
                    type="text" 
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="e.g. Precision Forge Ltd.">
                </div>
                <span v-if="errors.org_name" class="text-xs text-red-600">{{ errors.org_name[0] }}</span>
              </div>

              <!-- Industry Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Industry Type</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-industry"></i>
                  </span>
                  <select 
                    v-model="form.industry_type"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none">
                    <option value="">Select Industry</option>
                    <option value="manufacturing">Manufacturing</option>
                    <option value="automotive">Automotive</option>
                    <option value="electronics">Electronics</option>
                    <option value="textile">Textile</option>
                    <option value="food">Food & Beverage</option>
                    <option value="pharmaceutical">Pharmaceutical</option>
                    <option value="other">Other</option>
                  </select>
                  <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fas fa-chevron-down"></i>
                  </span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <!-- Company Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Company Email Address</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-envelope"></i>
                  </span>
                  <input 
                    v-model="form.primary_email"
                    type="email" 
                    required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="contact@company.com">
                </div>
                <span v-if="errors.primary_email" class="text-xs text-red-600">{{ errors.primary_email[0] }}</span>
              </div>

              <!-- Mobile Number -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mobile Number</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-phone"></i>
                  </span>
                  <input 
                    v-model="form.primary_phone"
                    type="tel"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="+1 (555) 000-0000">
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <!-- Country & State -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Country & State</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-globe"></i>
                  </span>
                  <input 
                    v-model="form.state"
                    type="text"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="United States, California">
                </div>
              </div>

              <!-- GST/Tax ID -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">GST Number / Tax ID</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-file-invoice"></i>
                  </span>
                  <input 
                    v-model="form.tax_id"
                    type="text"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="22AAAAA0000A1Z5">
                </div>
              </div>
            </div>

            <!-- Company Logo Upload -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
              <div 
                @click="$refs.logoInput.click()"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                :class="{'border-blue-400 bg-blue-50': isDragging}"
                class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-400 transition-colors cursor-pointer">
                <div class="flex flex-col items-center">
                  <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-image text-blue-600 text-2xl"></i>
                  </div>
                  <p class="text-gray-700 mb-1">Click to upload company logo or drag and drop</p>
                  <p class="text-sm text-gray-500">SVG, PNG, JPG or GIF (max. 800x400px)</p>
                  <input 
                    ref="logoInput"
                    type="file" 
                    @change="handleLogoChange"
                    accept="image/*" 
                    class="hidden">
                </div>
              </div>
              <div v-if="logoPreview" class="mt-4">
                <img :src="logoPreview" alt="Logo preview" class="max-h-32 mx-auto rounded-lg">
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ errorMessage }}</span>
              </div>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
              <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ successMessage }}</span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
              <button 
                type="button" 
                @click="$router.back()"
                class="flex items-center text-gray-600 hover:text-gray-900 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Login
              </button>
              <button 
                type="submit" 
                :disabled="isSubmitting"
                class="px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors flex items-center disabled:opacity-50">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                {{ isSubmitting ? 'Processing...' : 'Save and Continue' }}
                <i v-if="!isSubmitting" class="fas fa-arrow-right ml-2"></i>
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-600">
        <span>© 2024 Fabricate ERP Systems. All rights reserved.</span>
        <div class="flex space-x-6">
          <a href="#" class="hover:text-gray-900">Privacy Policy</a>
          <a href="#" class="hover:text-gray-900">Terms of Service</a>
          <a href="#" class="hover:text-gray-900">Contact Support</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'OrganizationRegister',
  data() {
    return {
      form: {
        org_name: '',
        org_slug: '',
        primary_email: '',
        primary_phone: '',
        country_code: 'US',
        state: '',
        industry_type: '',
        tax_id: ''
      },
      logoFile: null,
      logoPreview: null,
      isDragging: false,
      isSubmitting: false,
      errors: {},
      errorMessage: '',
      successMessage: ''
    }
  },
  methods: {
    generateSlug() {
      this.form.org_slug = this.form.org_name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    },
    handleLogoChange(event) {
      const file = event.target.files[0];
      if (file) {
        this.logoFile = file;
        this.previewLogo(file);
      }
    },
    handleDrop(event) {
      this.isDragging = false;
      const files = event.dataTransfer.files;
      if (files.length > 0) {
        this.logoFile = files[0];
        this.previewLogo(files[0]);
      }
    },
    previewLogo(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.logoPreview = e.target.result;
      };
      reader.readAsDataURL(file);
    },
    async handleSubmit() {
      this.isSubmitting = true;
      this.errors = {};
      this.errorMessage = '';
      this.successMessage = '';

      try {
        const response = await fetch('/api/v1/organizations/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(this.form)
        });

        const data = await response.json();

        if (response.ok && data.success) {
          this.successMessage = data.message;
          
          // Redirect to next step after 2 seconds
          setTimeout(() => {
            this.$router.push('/subscription');
          }, 2000);
        } else {
          if (data.error && data.error.details) {
            this.errors = data.error.details;
          }
          this.errorMessage = data.message || 'We could not complete your registration. Please check your details and try again.';
        }
      } catch (error) {
        this.errorMessage = 'Network error while submitting registration. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    }
  }
}
</script>
