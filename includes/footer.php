</main>
    
    <!-- Footer -->
    <footer class="footer text-white pt-12 pb-6">
        <div class="container m-l-r mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center mb-4">
                        <img src="assets/images/footer-logo.png" alt="RapidStay" class="h-8">
                        <!-- <span class="ml-2 text-xl font-bold">RapidStay</span> -->
                    </div>
                    <p class="text-gray-400 mb-4">
                        Find your perfect stay with RapidStay - connecting people with comfortable accommodations since 2023.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- About -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">About</h3>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-400 hover:text-white transition duration-300">About Us</a></li>
                        <li><a href="how-it-works.php" class="text-gray-400 hover:text-white transition duration-300">How It Works</a></li>
                        <li><a href="careers.php" class="text-gray-400 hover:text-white transition duration-300">Careers</a></li>
                        <li><a href="blog.php" class="text-gray-400 hover:text-white transition duration-300">Blog</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition duration-300">Contact Us</a></li>
                    </ul>
                </div>
                
                <!-- Flatmates & PGs -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Accommodations</h3>
                    <ul class="space-y-2">
                        <li><a href="explore.php?type=room" class="text-gray-400 hover:text-white transition duration-300">Rooms</a></li>
                        <li><a href="explore.php?type=roommate" class="text-gray-400 hover:text-white transition duration-300">Roommates</a></li>
                        <li><a href="explore.php?type=pg" class="text-gray-400 hover:text-white transition duration-300">PG Accommodations</a></li>
                        <li><a href="explore.php?premium=1" class="text-gray-400 hover:text-white transition duration-300">Premium Properties</a></li>
                        <li><a href="cities.php" class="text-gray-400 hover:text-white transition duration-300">Popular Cities</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Our Services</h3>
                    <ul class="space-y-2">
                        <li><a href="rental-agreement.php" class="text-gray-400 hover:text-white transition duration-300">Rental Agreement</a></li>
                        <li><a href="packers-movers.php" class="text-gray-400 hover:text-white transition duration-300">Packers & Movers</a></li>
                        <li><a href="property-management.php" class="text-gray-400 hover:text-white transition duration-300">Property Management</a></li>
                        <li><a href="legal-services.php" class="text-gray-400 hover:text-white transition duration-300">Legal Services</a></li>
                        <li><a href="help-center.php" class="text-gray-400 hover:text-white transition duration-300">Help Center</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        &copy; <?php echo date('Y'); ?> RapidStay. All rights reserved.
                    </p>
                    <div class="flex space-x-6">
                        <a href="terms.php" class="text-gray-400 hover:text-white text-sm transition duration-300">Terms of Service</a>
                        <a href="privacy.php" class="text-gray-400 hover:text-white text-sm transition duration-300">Privacy Policy</a>
                        <a href="cookies.php" class="text-gray-400 hover:text-white text-sm transition duration-300">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="back-to-top" class="fixed bottom-6 btn-bg right-6 bg-blue-600 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg opacity-0 invisible transition-all duration-300">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <script>
        // Back to top button
        const backToTopButton = document.getElementById('back-to-top');
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.remove('opacity-0', 'invisible');
                backToTopButton.classList.add('opacity-100', 'visible');
            } else {
                backToTopButton.classList.remove('opacity-100', 'visible');
                backToTopButton.classList.add('opacity-0', 'invisible');
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>

