# Comment Application

A modern web application for managing comments and discussions, built with Laravel and Vue.js.

## Features

### Comments and Replies
- Create and manage comments
- Support for nested replies (threaded comments)
- Pagination for both comments and replies
- Real-time updates without page reload
- Image upload support for comments
- XSS protection and HTML sanitization

### User Management
- User registration with unique names and emails
- Optional homepage links for users
- Automatic user creation when posting comments
- User data validation and duplicate prevention

### Sorting and Navigation
- Sort comments by date (newest/oldest)
- Dynamic loading of replies
- Smooth scrolling to new comments
- Responsive design for all devices

### Security Features
- CSRF protection
- Input validation and sanitization
- XSS prevention
- Secure file upload handling
- Rate limiting for comment creation

### User Interface
- Modern and clean design
- Responsive layout
- Loading states and animations
- Error handling and user feedback
- Avatar support for users

## Technical Stack

### Frontend
- Vue.js 3 with Composition API
- Tailwind CSS for styling
- Axios for API requests
- Alpine.js for enhanced interactivity
- Laravel Mix for asset compilation

### Backend
- Laravel 10.x
- MySQL database
- Laravel Sanctum for API authentication
- Laravel's built-in security features

Message Queue Integration
Receiving External Comments via RabbitMQ
The application receives comments from an external website — https://abz.prototypecodetest.site/ — using RabbitMQ and WebSocket integration.

A custom Artisan command RabbitConsumeComments listens for incoming messages via a WebSocket-connected RabbitMQ queue.

Received comment data is processed by the RabbitReserveCommentService, which handles validation and transformation.

After processing, the comment is stored in the local database alongside regular user-submitted comments.

This real-time background process ensures smooth ingestion of external comment streams and is designed for stable continuous operation.

## Database Structure

### Users Table
- `id` - Primary key
- `name` - Unique username
- `email` - Unique email address
- `homepage` - Optional user website
- `created_at` and `updated_at` timestamps

### Comments Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `parent_id` - For nested comments
- `text` - Comment content
- `image_path` - Optional image attachment
- `created_at` and `updated_at` timestamps

### Captchas Table
- `id` - Primary key
- `token` - Unique captcha token
- `text` - Captcha text
- `created_at` and `updated_at` timestamps

## Installation

1. Clone the repository:
```bash
git clone https://github.com/dmit9/laravel-commentApp-Vue.git
cd comment-app
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Create environment file:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comment_app
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Build frontend assets:
```bash
npm run build
```

8. Start the development server:
```bash
php artisan serve
```

## Usage Guide

### Creating a Comment
1. Fill in the comment form at the top of the page
2. Enter your name and email (required)
3. Optionally add your homepage URL
4. Write your comment text
5. Optionally attach an image
6. Click "Submit"

### Replying to Comments
1. Click the "Reply" button on any comment
2. Fill in the reply form that appears
3. Enter your details if you haven't before
4. Write your reply
5. Click "Submit"

### Managing Comments
- Use the sort buttons to change comment order
- Click "Load More" to see additional replies
- Images in comments are displayed in a modal view
- Use the pagination controls to navigate through pages

### Security Notes
- All user inputs are sanitized
- File uploads are validated and secured
- Rate limiting prevents spam
- CSRF protection is enabled
- XSS attacks are prevented

## Development

### Frontend Development
```bash
npm run dev
```

### Backend Development
```bash
php artisan serve
```

### Running Tests
```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
