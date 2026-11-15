# Changelog

## Version 1.0.9 - 2025-11-06

### Fixed
- **Checkbox Alignment**: Fixed checkbox drift to the left, added proper margin (8px left padding, 5px margins)
- **Button Color Conflict**: Changed bulk delete button from red to blue (WordPress primary color) to differentiate from individual delete buttons
- Improved visual hierarchy - individual delete (red) for critical action, bulk delete (blue) for batch operations
- Added subtle shadow to bulk delete button for better depth

### Improved
- Better checkbox column spacing and alignment
- Clearer visual distinction between bulk and individual delete actions
- More professional button styling following WordPress admin patterns

## Version 1.0.8 - 2025-11-06

### Added
- **Bulk Delete Functionality**: Select and delete multiple requests at once
- Checkbox column for selecting individual requests
- "Select All" checkbox in table header to select all visible requests
- "Delete Selected" button that appears when requests are selected
- Real-time counter showing number of selected requests
- Font Awesome icons for delete buttons
- Animated slide-down for bulk actions bar
- Loading spinner in delete buttons during operation

### Improved
- Delete buttons now use Font Awesome trash icon instead of text
- Better visual feedback with icon animations (spinner during delete)
- Confirmation prompts adjusted for single vs multiple deletions
- Enhanced user experience with smooth animations
- Cleaner, more professional UI with icon-based actions

### Technical
- Font Awesome 6.4.0 integration via CDN
- Bulk delete AJAX endpoint (`sdr_bulk_delete_requests`)
- Checkbox state management in JavaScript
- Enhanced `delete_requests()` method implementation
- Selection tracking with real-time updates
- Proper pluralization for success messages (_n function)

## Version 1.0.7 - 2025-11-06

### Added
- **Delete Functionality**: Delete individual authenticity requests from the admin dashboard
- Delete button in the Actions column for each request
- Confirmation dialog before deletion to prevent accidental removals
- Visual feedback during deletion (button shows "Deleting...")
- Smooth row fade-out animation on successful deletion
- Auto-refresh of counts after deletion

### Improved
- Admin table now has 8 columns (added Actions column)
- Enhanced security with permission checks for delete operations
- Better user experience with confirmation prompts

### Technical
- New `delete_request()` method in database class
- New `delete_requests()` method for bulk operations (future use)
- AJAX endpoint for deletion (`sdr_delete_request`)
- Proper error handling and user feedback
- SQL injection protection with prepared statements

## Version 1.0.6 - 2025-11-06

### Added
- **Sortable Columns**: Click column headers to sort requests by ID, Product, Email, Status, Request Date, or Verification Date
- **AJAX-Driven Search**: Real-time search functionality for emails, product IDs, and OTP codes
- **Status Filter**: Filter requests by All, Verified, or Pending status
- **Live Results Count**: Dynamic display of showing/total requests
- **Loading States**: Visual feedback during data loading
- **Responsive Table**: Mobile-friendly admin interface

### Improved
- Admin page performance with AJAX-driven data loading
- User experience with instant search and sort
- Visual indicators for sorted columns (up/down arrows)
- Clean, modern admin interface design
- Better data presentation with color-coded status badges

### Technical
- New database methods for filtered/sorted queries
- AJAX endpoint for request retrieval (`sdr_get_requests`)
- Separate admin-list.js and admin-list.css for list functionality
- SQL injection protection with prepared statements
- Proper permission checks for admin operations

## Version 1.0.5 - 2025-11-06

### Added
- **Website Logo in Emails**: All email templates now display the website logo at the top
- Logo support for custom logos (set via WordPress Customizer)
- Fallback to site icon if custom logo is not set
- Professional white background for logos in colored email headers

### Improved
- Enhanced email branding with logo display
- Better visual identity in all email communications
- Responsive logo sizing (max 200px width, 80px height)

## Version 1.0.4 - 2025-11-06

### Changed
- **Removed PDF Viewer**: No longer displays PDF in full-screen modal after OTP verification
- **Email-Only Delivery**: PDF document is now only sent via email attachment (not displayed in browser)
- **Enhanced Success Message**: Updated to inform users to check both inbox and junk/spam folders
- **PDF Upload Button**: Added media library upload button in product settings for easier PDF management

### Added
- Media uploader integration in WooCommerce product edit page
- "Upload PDF" button with WordPress media library integration
- "Remove" button to clear PDF URL
- Admin JavaScript for handling media uploads

### Improved
- Simplified user flow - no more PDF viewing step
- Better user guidance about email delivery
- Easier PDF management for administrators

### Removed
- Full-screen PDF viewer modal
- Step 3 (PDF Display) from the modal workflow

## Version 1.0.3 - 2025-11-06

### Added
- **PDF Email Attachment**: Automatically sends the authenticity PDF document as an email attachment to the customer after successful OTP verification
- Email template for authenticity document delivery
- Support for both local WordPress media files and external PDF URLs
- Automatic temporary file cleanup after sending
- Enhanced success message indicating email has been sent

### Email Functionality
- Three types of emails now sent:
  1. OTP verification code to customer
  2. Admin notification for new requests
  3. Authenticity document with PDF attachment after verification

### PDF Handling
- Smart PDF path detection (local WordPress files vs external URLs)
- Automatic download of external PDFs for attachment
- Fallback to email without attachment if PDF is unavailable
- Comprehensive error logging for troubleshooting

## Version 1.0.2 - 2025-11-06

### Added
- **Conditional Button Display**: "Click Here For Proof of Authenticity" button only shows on products that have an Authenticity PDF URL configured
- **Full-Screen PDF Viewer**: Modal expands to full screen when displaying the PDF document after OTP verification
- Smooth animation transitions for modal expansion
- Improved PDF viewing experience with maximized viewport

### Changed
- Button text updated to "Click Here For Proof of Authenticity"
- Modal CSS updated with full-screen support
- JavaScript enhanced to handle modal size transitions

## Version 1.0.1 - 2025-11-06

### Added
- Admin email notifications for new authenticity requests
- Detailed email template with customer info, product details, and quick action links
- Enhanced debugging and error logging

### Fixed
- Product ID validation and error handling
- JavaScript issue with product ID being reset on modal open

## Version 1.0.0 - Initial Release

### Features
- OTP verification system for authenticity documents
- Email-based OTP delivery
- Single-use OTP codes
- WooCommerce product page integration
- Modal-based user interface
- Admin dashboard for viewing requests
- Database storage for all requests
- Product-level PDF configuration

