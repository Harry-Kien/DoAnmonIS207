-- Cập nhật thông tin cho gói cơ bản
UPDATE plans 
SET features = '{
    "search": true,
    "view_details": true,
    "contact_owner": true,
    "save_favorite": true,
    "post_room": false,
    "featured_post": false,
    "priority_support": false,
    "free_consultation": false,
    "insurance": false
}',
duration = 365,
price = 0,
name = "Cơ bản",
max_posts = 0
WHERE plan_code = 'basic';

-- Cập nhật thông tin cho gói phổ biến (standard)
UPDATE plans 
SET features = '{
    "search": true,
    "view_details": true,
    "contact_owner": true,
    "save_favorite": true,
    "post_room": true,
    "featured_post": false,
    "priority_support": true,
    "free_consultation": false,
    "insurance": false
}',
duration = 30,
price = 199000,
name = "Phổ biến",
max_posts = null
WHERE plan_code = 'standard';

-- Cập nhật thông tin cho gói cao cấp
UPDATE plans 
SET features = '{
    "search": true,
    "view_details": true,
    "contact_owner": true,
    "save_favorite": true,
    "post_room": true,
    "featured_post": true,
    "priority_support": true,
    "free_consultation": true,
    "insurance": true
}',
duration = 30,
price = 399000,
name = "Cao cấp",
max_posts = null
WHERE plan_code = 'premium'; 