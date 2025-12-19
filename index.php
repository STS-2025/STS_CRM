<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - STS CRM</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>


<body class="bg-gradient-to-br from-[#0B1B3A] to-[#12285a] min-h-screen flex items-center justify-center px-4">

  <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8">
    <!-- Logo -->
    <div class="flex flex-col items-center mb-6">
      <img src="assets/images/logo.png" alt="STS Logo" class="w-16 h-16 mb-2">
      <h2 class="text-2xl font-bold text-[#0B1B3A]">Welcome Back 👋</h2>
      <p class="text-gray-500 text-sm">Sign in to continue using STS CRM</p>
    </div>

    <!-- Login Form -->
    <form action="api/login_process.php" method="POST" class="space-y-4">

      <!-- Role Selection -->
      <div>
        <select name="role" required
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
          <option value="">Select Role</option>
          <option value="admin">Admin</option>
          <option value="user">User</option>
        </select>
      </div>

      <!-- Email -->
      <div class="relative">
        <input type="email" id="email" name="email" required
          placeholder="Email address"
          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
        <!-- Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18" />
        </svg>
      </div>

      <!-- Password -->
      <div class="relative">
        <input type="password" id="password" name="password" required
          placeholder="Password"
          class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#00AEEF]">
        <!-- Lock Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m0 0a2 2 0 100-4 2 2 0 000 4zM7 10V8a5 5 0 1110 0v2" />
        </svg>

        <!-- Show/Hide Password -->
        <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
          <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>

      <!-- Submit Button -->
      <button type="submit"
        class="w-full bg-[#00AEEF] hover:bg-[#0095cc] text-white font-semibold py-2 rounded-lg transition">
        Login
      </button>
    </form>

    <!-- Links -->
    <div class="text-center mt-4 text-sm text-gray-600">
      <p>Don’t have an account?
        <a href="register.php" class="text-[#00AEEF] font-medium hover:underline">Register</a>
      </p>
      <p class="mt-1">
        <a href="#" class="text-[#00AEEF] hover:underline">Forgot password?</a>
      </p>
    </div>
  </div>

  <!-- Password Toggle Script -->
  <script>
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

<?php
if (isset($_POST['login'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];
  
  $result = $conn->query("SELECT * FROM users WHERE email='$email'");
  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      session_start();
      $_SESSION['user'] = $user['name'];
      header("Location: dashboard.php");
    } else {
      echo "Invalid password!";
    }
  } else {
    echo "User not found!";
  }
}
?>