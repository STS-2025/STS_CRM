<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - STS CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/@heroicons/react/outline"></script>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-sky-500 to-cyan-600">

  <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
    <div class="flex flex-col items-center mb-6">
      <img src="assets/images/logo.png" alt="CRM Logo" class="w-16 mb-2">
      <h2 class="text-2xl font-bold text-sky-600">Create Your Account</h2>
      <p class="text-gray-500 text-sm mt-1">Join STS CRM and manage your business efficiently!</p>
    </div>

    <form action="api/register_process.php" method="POST" class="space-y-4">
      <!-- Full Name -->
      <div class="relative">
        <input type="text" name="name" placeholder="Full Name" required
          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A10 10 0 1112 22a9.98 9.98 0 01-6.879-4.196z" />
        </svg>
      </div>

      <!-- Email -->
      <div class="relative">
        <input type="email" name="email" placeholder="Email Address" required
          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18" />
        </svg>
      </div>

      <!-- Password -->
      <div class="relative">
        <input type="password" name="password" id="password" placeholder="Password" required
          class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m0 0a2 2 0 100-4 2 2 0 000 4zM7 10V8a5 5 0 1110 0v2" />
        </svg>
        <!-- Show/hide password -->
        <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
          <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>

      <!-- Role -->
      <div class="relative">
        <select name="role" required
          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-sky-400 focus:outline-none">
          <option value="">Select Role</option>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6" />
        </svg>
      </div>

      <!-- Submit -->
      <button type="submit"
        class="w-full bg-sky-600 hover:bg-sky-700 text-white font-semibold py-2 rounded-lg transition duration-200">
        Register
      </button>
    </form>

    <p class="text-sm text-gray-600 text-center mt-4">
      Already have an account?
      <a href="index.php" class="text-sky-600 font-semibold hover:underline">Login</a>
    </p>
  </div>

  <script>
    // Toggle password visibility
    function togglePassword() {
      const pass = document.getElementById("password");
      const eye = document.getElementById("eyeIcon");
      if (pass.type === "password") {
        pass.type = "text";
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.057-3.363 3.822-5.994 7.198-6.787M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
      } else {
        pass.type = "password";
        eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
      }
    }
  </script>
</body>
</html>
