<a href="listing.php?id=<?php echo $listing['id']; ?>" class="block group">
    <div class="bg-white bordet rounded-lg overflow-hidden shadow-sm group-hover:shadow-md transition duration-300 h-full">
        <div class="relative">
            <div class="h-48 overflow-hidden">
                <img 
                    src="<?php echo !empty($listing['primary_image']) ? htmlspecialchars($listing['primary_image']) : 'assets/images/placeholder.jpg'; ?>" 
                    alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                    class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                >
            </div>
            
            <div class="absolute top-3 right-3">
                <span class="px-2 py-1 bg-white text-xs font-medium rounded-full">
                    <?php echo htmlspecialchars(ucfirst($listing['type'])); ?>
                </span>
            </div>
            
            <?php if ($listing['is_premium']): ?>
                <div class="absolute top-3 left-3">
                    <span class="px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-medium rounded-full">
                        Premium
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                // Check if in wishlist
                $user_id = $_SESSION['user_id'];
                $wishlist_check = "SELECT id FROM wishlist WHERE user_id = $user_id AND listing_id = " . $listing['id'];
                $wishlist_result = mysqli_query($conn, $wishlist_check);
                $in_wishlist = mysqli_num_rows($wishlist_result) > 0;
                ?>
                <button 
                    class="absolute bottom-3 right-3 w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center wishlist-toggle"
                    data-listing-id="<?php echo $listing['id']; ?>"
                    data-in-wishlist="<?php echo $in_wishlist ? '1' : '0'; ?>"
                >
                    <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart text-<?php echo $in_wishlist ? 'red' : 'gray'; ?>-500"></i>
                </button>
            <?php endif; ?>
        </div>
        
        <div class="p-4">
            <div class="flex items-center text-sm text-gray-500 mb-1">
                <i class="fas fa-map-marker-alt mr-1"></i>
                <span><?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?></span>
            </div>
            
            <h3 class="font-bold text-lg mb-1 group-hover:text-blue-600 transition duration-300">
                <?php echo htmlspecialchars($listing['title']); ?>
            </h3>
            
            <div class="flex items-center mb-2">
                <div class="flex text-yellow-400 mr-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= round($listing['rating'])): ?>
                            <i class="fas fa-star text-xs"></i>
                        <?php elseif ($i - 0.5 <= $listing['rating']): ?>
                            <i class="fas fa-star-half-alt text-xs"></i>
                        <?php else: ?>
                            <i class="far fa-star text-xs"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <span class="text-sm text-gray-500">(<?php echo $listing['reviews_count']; ?>)</span>
            </div>
            
            <div class="flex justify-between items-end">
                <div class="font-bold text-lg text-blue-600">
                    ₹<?php echo number_format($listing['price']); ?>
                    <span class="text-xs font-normal text-gray-500">/month</span>
                </div>
                
                <div class="text-sm text-gray-500">
                    <?php echo date('M d', strtotime($listing['available_from'])); ?>
                </div>
            </div>
        </div>
    </div>
</a>

