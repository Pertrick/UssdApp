<script setup>
import { Link } from '@inertiajs/vue3'

const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Navigation -->
    <nav class="relative px-6 py-4">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-3">
            <h1 class="text-xl font-bold text-gray-900">USSD Pro</h1>
            <p class="text-sm text-gray-600">Enterprise USSD Solutions</p>
          </div>
        </div>
        
        <div class="hidden md:flex items-center space-x-8">
          <a href="#features" class="text-gray-700 hover:text-blue-600 transition-colors">Features</a>
          <a href="#pricing" class="text-gray-700 hover:text-blue-600 transition-colors">Pricing</a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 transition-colors">Contact</a>
          <Link :href="route('login')" class="text-gray-700 hover:text-blue-600 transition-colors">Login</Link>
          <Link :href="route('business.register')" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Get Started
          </Link>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative px-6 py-20">
      <div class="max-w-7xl mx-auto text-center">
        <div class="mb-8">
          <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mb-6">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            Trusted by 500+ Businesses
          </span>
        </div>
        
        <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
          Transform Your Business with
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
            USSD Technology
          </span>
        </h1>
        
        <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto leading-relaxed">
          Build powerful, interactive USSD applications that engage customers, streamline operations, and drive revenue growth. 
          No technical expertise required.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Link :href="route('business.register')" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            Start Free Trial
          </Link>
          <button @click="scrollToSection('demo')" class="border-2 border-gray-300 text-gray-700 px-8 py-4 rounded-lg text-lg font-semibold hover:border-blue-600 hover:text-blue-600 transition-all duration-200">
            Watch Demo
          </button>
        </div>
        
        <div class="mt-12 flex justify-center">
          <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-4xl">
            <div class="text-center mb-6">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Live USSD Simulator</h3>
              <p class="text-gray-600">Experience the power of USSD technology</p>
            </div>
            <div class="bg-gray-900 rounded-lg p-4 text-green-400 font-mono text-sm">
              <div class="mb-2">Welcome to USSD Pro</div>
              <div class="mb-2">1. Check Balance</div>
              <div class="mb-2">2. Transfer Money</div>
              <div class="mb-2">3. Buy Airtime</div>
              <div class="mb-2">4. Pay Bills</div>
              <div class="mb-2">5. Customer Care</div>
              <div class="mb-2">0. Exit</div>
              <div class="text-white">Enter your choice:</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="px-6 py-20 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Why Choose USSD Pro?</h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Powerful features designed to help businesses of all sizes succeed with USSD technology
          </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div class="text-center p-6">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Lightning Fast</h3>
            <p class="text-gray-600">USSD works on all phones without internet. Instant responses, no data required.</p>
          </div>
          
          <div class="text-center p-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure & Reliable</h3>
            <p class="text-gray-600">Bank-grade security with 99.9% uptime. Your data is always protected.</p>
          </div>
          
          <div class="text-center p-6">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Real-time Analytics</h3>
            <p class="text-gray-600">Monitor performance, track user behavior, and optimize your USSD flows.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Use Cases Section -->
    <section class="px-6 py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Perfect for Every Industry</h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            From banking to healthcare, USSD Pro helps businesses across all sectors
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">Banking & Finance</h3>
            <p class="text-sm text-gray-600">Balance checks, transfers, bill payments, and account management</p>
          </div>
          
          <div class="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">Healthcare</h3>
            <p class="text-sm text-gray-600">Appointment booking, health tips, emergency contacts</p>
          </div>
          
          <div class="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">E-commerce</h3>
            <p class="text-sm text-gray-600">Order tracking, product catalogs, customer support</p>
          </div>
          
          <div class="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">Government</h3>
            <p class="text-sm text-gray-600">Service requests, information access, citizen engagement</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="px-6 py-20 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Choose the plan that fits your business needs. No hidden fees, no surprises.
          </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Starter Plan -->
          <div class="bg-white border-2 border-gray-200 rounded-lg p-8 hover:border-blue-500 transition-colors">
            <div class="text-center mb-8">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
              <div class="text-4xl font-bold text-gray-900 mb-2">$29<span class="text-lg text-gray-600">/month</span></div>
              <p class="text-gray-600">Perfect for small businesses</p>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Up to 1,000 sessions/month
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Basic analytics
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Email support
              </li>
            </ul>
            <Link :href="route('business.register')" class="w-full bg-gray-100 text-gray-900 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition-colors block text-center">
              Get Started
            </Link>
          </div>
          
          <!-- Professional Plan -->
          <div class="bg-white border-2 border-blue-500 rounded-lg p-8 relative">
            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
              <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Most Popular</span>
            </div>
            <div class="text-center mb-8">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
              <div class="text-4xl font-bold text-gray-900 mb-2">$99<span class="text-lg text-gray-600">/month</span></div>
              <p class="text-gray-600">For growing businesses</p>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Up to 10,000 sessions/month
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Advanced analytics
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Priority support
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Custom branding
              </li>
            </ul>
            <Link :href="route('business.register')" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors block text-center">
              Get Started
            </Link>
          </div>
          
          <!-- Enterprise Plan -->
          <div class="bg-white border-2 border-gray-200 rounded-lg p-8 hover:border-blue-500 transition-colors">
            <div class="text-center mb-8">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
              <div class="text-4xl font-bold text-gray-900 mb-2">$299<span class="text-lg text-gray-600">/month</span></div>
              <p class="text-gray-600">For large organizations</p>
            </div>
            <ul class="space-y-4 mb-8">
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Unlimited sessions
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Custom integrations
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                24/7 phone support
              </li>
              <li class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Dedicated account manager
              </li>
            </ul>
            <Link :href="route('business.register')" class="w-full bg-gray-100 text-gray-900 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition-colors block text-center">
              Contact Sales
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="px-6 py-20 bg-gradient-to-r from-blue-600 to-indigo-600">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-bold text-white mb-4">Ready to Transform Your Business?</h2>
        <p class="text-xl text-blue-100 mb-8">
          Join thousands of businesses already using USSD Pro to engage customers and drive growth
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link :href="route('business.register')" class="bg-white text-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors">
            Start Free Trial
          </Link>
          <button @click="scrollToSection('contact')" class="border-2 border-white text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors">
            Talk to Sales
          </button>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-gray-900 text-white px-6 py-12">
      <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-8">
          <div>
            <div class="flex items-center mb-4">
              <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-xl font-bold">USSD Pro</span>
            </div>
            <p class="text-gray-400 mb-4">
              The leading platform for building powerful USSD applications that engage customers and drive business growth.
            </p>
          </div>
          
          <div>
            <h3 class="font-semibold mb-4">Product</h3>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white transition-colors">Features</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Pricing</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
              <li><a href="#" class="hover:text-white transition-colors">API</a></li>
            </ul>
          </div>
          
          <div>
            <h3 class="font-semibold mb-4">Company</h3>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white transition-colors">About</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
            </ul>
          </div>
          
          <div>
            <h3 class="font-semibold mb-4">Support</h3>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Community</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Status</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
            </ul>
          </div>
        </div>
        
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; 2024 USSD Pro. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>
