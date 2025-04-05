</div>
  </div>
  
  <script>
      // Mobile sidebar toggle
      document.getElementById('open-sidebar').addEventListener('click', function() {
          document.getElementById('mobile-sidebar').style.display = 'flex';
      });
      
      document.getElementById('close-sidebar').addEventListener('click', function() {
          document.getElementById('mobile-sidebar').style.display = 'none';
      });
      
      // User menu toggle
      const userMenuButton = document.getElementById('user-menu-button');
      const userMenu = document.getElementById('user-menu');
      
      userMenuButton.addEventListener('click', function(e) {
          e.stopPropagation();
          userMenu.classList.toggle('hidden');
      });
      
      // Close user menu when clicking outside
      document.addEventListener('click', function(event) {
          if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
              userMenu.classList.add('hidden');
          }
      });
  </script>
</body>
</html>

