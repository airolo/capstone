<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MySchedMate</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html {
      scroll-behavior: smooth;
    }
  </style>
  <!-- AOS Animate on Scroll CSS & JS -->
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    AOS.init();
  });
</script>

</head>
<body class="bg-white text-gray-800 font-sans">

  <!-- NAVIGATION BAR -->
  <nav class="bg-white shadow-md fixed w-full z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
      <a href="#hero" class="text-xl font-bold text-blue-600">MySchedMate</a>
      <div class="space-x-6 hidden md:block">
        <a href="#features" class="text-gray-700 hover:text-blue-600 transition">Features</a>
        <a href="#preview" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
        <a href="#sdg" class="text-gray-700 hover:text-blue-600 transition">SDG</a>
        <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition">Login</a>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <header id="hero" class="bg-blue-600 text-white pt-32 pb-20 text-center">
    <div class="max-w-7xl mx-auto px-6">
      <h1 class="text-4xl md:text-6xl font-bold mb-4">MySchedMate</h1>
      <p class="text-lg md:text-xl mb-6">A Scheduler Tool and Attendance Monitoring Web App for Student Assistants</p>
      <a href="signup.php" class="bg-white text-blue-600 font-semibold px-6 py-3 rounded-full hover:bg-gray-100 transition">Get Started</a>
    </div>
  </header>

  <!-- FEATURES SECTION -->
  <section id="features" class="py-20 bg-gray-50">
  <div class="max-w-6xl mx-auto px-6">
    <h2 class="text-3xl font-bold text-center mb-12">Key Features</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">

      <!-- Feature 1 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="100">
        <h3 class="text-xl font-semibold mb-2">🧠 Automatic Scheduling</h3>
        <p>Generates conflict-free work schedules based on class availability.</p>
      </div>

      <!-- Feature 2 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="200">
        <h3 class="text-xl font-semibold mb-2">📱 QR Code Time-In/Out</h3>
        <p>Track attendance securely using dynamic QR codes and live timestamps.</p>
      </div>

      <!-- Feature 3 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="300">
        <h3 class="text-xl font-semibold mb-2">🕒 Make-Up Hours</h3>
        <p>Automatically reschedules missed hours without class conflict.</p>
      </div>

      <!-- Feature 4 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="400">
        <h3 class="text-xl font-semibold mb-2">🧑‍💼 Admin Dashboard</h3>
        <p>Manage users, monitor logs, and generate reports in real time.</p>
      </div>

      <!-- Feature 5 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="500">
        <h3 class="text-xl font-semibold mb-2">🔔 Notifications</h3>
        <p>Stay updated with schedule changes, approvals, and reminders.</p>
      </div>

      <!-- Feature 6 -->
      <div class="p-6 bg-white rounded-2xl shadow-md" data-aos="fade-up" data-aos-delay="600">
        <h3 class="text-xl font-semibold mb-2">📅 Visual Calendar</h3>
        <p>View class and work schedules in an easy-to-navigate calendar.</p>
      </div>

    </div>
  </div>
</section>


  <!-- DASHBOARD PREVIEW -->
  <section id="preview" class="py-20">
    <div class="max-w-5xl mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold mb-4">Preview the Dashboard</h2>
      <p class="mb-8 text-gray-600">Clean, responsive, and role-based interface for admins and assistants.</p>
      <img src="assets/dashboard-placeholder.png" alt="Dashboard Preview" class="rounded-xl shadow-lg mx-auto max-w-full" />
    </div>
  </section>

  <!-- UN SDGs SECTION -->
  <section id="sdg" class="py-20 bg-blue-100">
  <div class="max-w-6xl mx-auto px-6 text-center">
    <h2 class="text-3xl font-bold mb-6 text-blue-900">Aligned with the UN Sustainable Development Goals</h2>
    <p class="mb-10 text-blue-800">MySchedMate directly supports the following global development goals:</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- SDG 4 -->
      <div class="bg-white border-2 border-blue-700 p-8 rounded-2xl shadow-md flex flex-col justify-between text-left"
           data-aos="fade-up" data-aos-delay="100" data-aos-duration="800">
        <div>
          <h3 class="text-2xl font-bold text-blue-700 mb-2">🎓 Goal 4: Quality Education</h3>
          <p class="text-gray-700">
            Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all.
            MySchedMate supports this by helping student assistants manage their time effectively so they can prioritize learning.
          </p>
        </div>
      </div>

      <!-- SDG 8 -->
      <div class="bg-white border-2 border-blue-700 p-8 rounded-2xl shadow-md flex flex-col justify-between text-left"
           data-aos="fade-up" data-aos-delay="200" data-aos-duration="800">
        <div>
          <h3 class="text-2xl font-bold text-blue-700 mb-2">💼 Goal 8: Decent Work and Economic Growth</h3>
          <p class="text-gray-700">
            Promote sustained, inclusive, and sustainable economic growth, full and productive employment, and decent work for all.
            MySchedMate enables fair work hour tracking, schedule automation, and transparency for student workers.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>



    </div>
  </section>

  <!-- FOOTER / CTA -->
  <footer class="bg-blue-700 text-white text-center py-10">
    <h3 class="text-2xl font-semibold mb-2">Ready to experience smart scheduling?</h3>
    <a href="signup.php" class="bg-white text-blue-700 font-bold px-6 py-3 rounded-full hover:bg-gray-100 transition">Sign Up Now</a>
    <p class="mt-4 text-sm">© 2025 MySchedMate. All rights reserved.</p>
  </footer>

</body>
</html>
