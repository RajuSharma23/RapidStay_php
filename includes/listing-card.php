<?php
// Format image URL correctly
$image_url = getImageUrl($listing['primary_image'] ?? '');

// Get proper listing URL
$listing_url = "listing.php?id=" . $listing['id'];
?>

<div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border-top overflow-hidden">
    <!-- Listing Image -->
    <div class="relative h-48">
        <a href="<?php echo htmlspecialchars($listing_url); ?>">
            <img 
                src="<?php echo htmlspecialchars($image_url); ?>" 
                alt="<?php echo htmlspecialchars($listing['title']); ?>"
                class="w-full h-full object-cover"
                loading="lazy"
            >
        </a>
        
        <?php if (!empty($listing['is_premium'])): ?>
        <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 text-xs font-semibold rounded">
            PREMIUM
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Listing Details -->
    <div class="p-4">
        <h3 class="font-semibold text-lg mb-1 truncate">
            <a href="<?php echo htmlspecialchars($listing_url); ?>" class="hover:text-blue-600">
                <?php echo htmlspecialchars($listing['title']); ?>
            </a>
        </h3>
        
        <div class="text-gray-500 text-sm mb-2 truncate">
            <?php echo htmlspecialchars($listing['locality'] . ', ' . $listing['city']); ?>
        </div>
        
        <div class="flex items-center justify-between mb-3">
            <div class="font-bold text-lg text-blue-600">
                ₹<?php echo number_format($listing['price']); ?><span class="text-gray-400 font-normal text-sm">/month</span>
            </div>
            
            <?php if (!empty($listing['rating'])): ?>
            <div class="flex items-center">
                <i class="fas fa-star text-yellow-400 mr-1"></i>
                <span class="font-medium"><?php echo number_format($listing['rating'], 1); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center text-gray-600 text-sm space-x-4">
            <div class="flex items-center">
                <i class="fas fa-home mr-1"></i>
                <span><?php echo ucfirst(htmlspecialchars($listing['type'])); ?></span>
            </div>
            
            <?php if (!empty($listing['furnishing_type'])): ?>
            <div class="flex items-center">
                <i class="fas fa-couch mr-1"></i>
                <span><?php echo ucfirst(htmlspecialchars($listing['furnishing_type'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

