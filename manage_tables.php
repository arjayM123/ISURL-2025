<?php
session_start();
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Determine which tab is active
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'news';

// Initialize query based on active tab
$query = "";
switch($activeTab) {
    case 'news':
        $query = "SELECT * FROM news_events WHERE deleted_at IS NULL ORDER BY created_at DESC";
        break;
    case 'sliders':
        $query = "SELECT * FROM sliders WHERE deleted_at IS NULL ORDER BY created_at DESC";
        break;
    case 'publication':
        $query = "SELECT * FROM publications WHERE deleted_at IS NULL ORDER BY created_at DESC";
        break;
}

$stmt = $conn->query($query);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for session messages ONLY on redirect
$message = null;
$messageType = null;

if (isset($_SESSION['message'], $_SESSION['message_type']) && isset($_SERVER['HTTP_REFERER'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    // Clear the session messages immediately
    unset($_SESSION['message'], $_SESSION['message_type']);
}

include 'admin_layout.php';
?>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div class="container">
<?php if (!empty($message) && !empty($messageType)): ?>
<div class="modal-overlay" id="successModal">
    <div class="modal-content">
        <h2><?= $messageType === 'success' ? 'Success' : 'Notice' ?></h2>
        <p><?= htmlspecialchars($message) ?></p>
    </div>
</div>
<?php endif; ?>



    <div class="page-header">
        <h1>Manage Tables</h1>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
    <!-- Tab Navigation -->
    <div class="tab-navigation">
                <a href="?tab=sliders" class="tab-link <?php echo $activeTab === 'sliders' ? 'active' : ''; ?>">
            Sliders
        </a>
        <a href="?tab=news" class="tab-link <?php echo $activeTab === 'news' ? 'active' : ''; ?>">
            News & Events
        </a>
        <a href="?tab=publication" class="tab-link <?php echo $activeTab === 'publication' ? 'active' : ''; ?>">
            Publication
        </a>
    </div>
    <div class="bulk-actions">
        <button id="bulkDeleteBtn" class="btn btn-danger btn-sm" disabled>
            <i class="fas fa-trash" title="Delete Selected"></i>
        </button>
        <button id="bulkAddBtn" class="btn btn-success btn-sm" >
            <i class="fas fa-plus" title="Add New"></i>
        </button>
    </div>
    <!-- Table Container -->
    <div class="table-container">
        <table class="manage-table">
<thead>
<tr>
    <th><input type="checkbox" id="selectAll"></th>
    <th>ID</th>
    <?php if ($activeTab === 'news' || $activeTab === 'publication'): ?>
        <th>Title</th>
    <?php endif; ?>
    <th>Created At</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><input type="checkbox" class="item-checkbox" value="<?php echo $item['id']; ?>"></td>
        <td><?php echo htmlspecialchars($item['id']); ?></td>
        <?php if ($activeTab === 'news' || $activeTab === 'publication'): ?>
            <td><?php echo htmlspecialchars($item['title']); ?></td>
        <?php endif; ?>
        <td><?php echo date('F d, Y', strtotime($item['created_at'])); ?></td>
       <td class="actions">
            <?php if ($activeTab === 'sliders'): ?>
            <a href="index.php?id=<?php echo $item['id']; ?>" 
               class="btn btn-primary btn-sm view-btn"
               title="View Slider"
               data-id="<?php echo $item['id']; ?>"
               data-type="<?php echo $activeTab; ?>">
                <i class="fas fa-eye"></i>
            </a>
            <?php endif; ?>
            
<?php if ($activeTab === 'news'): ?>
    <a href="news_edit.php?id=<?php echo $item['id']; ?>" 
       class="btn btn-info btn-sm"
       title="Edit News/Event">
        <i class="fas fa-edit"></i>
    </a>
<?php endif; ?>
           <?php if ($activeTab === 'publication'): ?>
    <a href="publication_edit.php?id=<?php echo $item['id']; ?>" 
       class="btn btn-info btn-sm"
       title="Edit Publication">
        <i class="fas fa-edit"></i>
    </a>
    <a href="#" 
       class="btn btn-primary btn-sm view-btn"
       title="View Publication Images"
       data-id="<?php echo $item['id']; ?>"
       data-type="<?php echo $activeTab; ?>">
        <i class="fas fa-eye"></i>
    </a>
<?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
        </table>
    </div>
</div>
<div id="viewModal" >
    <div >
        <button onclick="closeModal();">&times;</button>
        <div id="modalBody"></div>
    </div>
</div>
<div class="delete-modal" id="deleteModal">
    <div class="delete-modal-content">
        <div class="delete-modal-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3 class="delete-modal-title">Confirm Deletion</h3>
        <p class="delete-modal-message">Are you sure you want to delete this item? This action cannot be undone.</p>
        <div class="delete-modal-buttons">
            <button class="btn-delete-cancel" onclick="cancelDelete()">Cancel</button>
            <button class="btn-delete-confirm" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>
</div>

<style>

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    max-width: 400px;
    width: 90%;
    animation: fadeInScale 0.3s ease;
}

.modal-content h2 {
    color: #216c2a;
    margin-bottom: 0.5rem;
}

.modal-content p {
    color: #333;
    font-size: 1rem;
}

.modal-content button {
    margin-top: 1rem;
    padding: 0.5rem 1rem;
    background: #216c2a;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease;
}

.modal-content button:hover {
    background: #1a5621;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}


    /* Add or update in your CSS */
#viewModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.7);
    align-items: center;
    justify-content: center;
}

#viewModal.active {
    display: flex;
}

#viewModal > div {
    padding: 2rem;
    max-width: 1000px;
    background:none;
    width: 100%;
    position: relative;
    margin: 100px auto;
}

#viewModal button {
    position: absolute;
    top: .5rem;
    right: .5rem;
    background: none;
    border: none;
    font-size: 2rem;
    color: #888;
    cursor: pointer;
    transition: color 0.2s; 
}

#viewModal button:hover {
    color: #216c2a;
}

#modalBody {
    margin-top: 1.5rem;
    text-align: center;
    word-break: break-word;
}

/* Responsive for modal */
@media  screen and(max-width: 600px) {
    #viewModal > div {
        padding: 1rem;
        max-width: 95vw;
    }
}
.tab-navigation {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.5rem;
}
.btn-info{
    background: #17a2b8;
    color: white;
}
.tab-link {
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #666;
    border-radius: 4px 4px 0 0;
    transition: all 0.3s ease;
}

.tab-link:hover {
    color: #216c2a;
}

.tab-link.active {
    color: #216c2a;
    border-bottom: 2px solid #216c2a;
    font-weight: bold;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.manage-table {
    width: 100%;
    border-collapse: collapse;
}

.manage-table th,
.manage-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.manage-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #216c2a;
}

.manage-table tr:hover {
    background: #f8f9fa;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
.bulk-actions {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}


.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    color: #2c3e50;
    font-size: 2rem;
    margin: 0;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.back-btn:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

/* Tab Navigation */
.tab-navigation {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #dee2e6;
    padding: 0;
}

.tab-link {
    padding: 1rem 2rem;
    text-decoration: none;
    color: #6c757d;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-link:hover {
    color: #216c2a;
    background: rgba(33, 108, 42, 0.05);
}

.tab-link.active {
    color: #216c2a;
    border-bottom: 3px solid #216c2a;
    font-weight: 600;
}

/* Bulk Actions */
.bulk-actions {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #dee2e6;
}

/* Table Styles */
.table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.manage-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.manage-table th,
.manage-table td {
    padding: 1.2rem 1rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.manage-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.manage-table tr:hover {
    background: #f8f9fa;
}

.manage-table tbody tr:last-child td {
    border-bottom: none;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.btn-primary {
    background: #216c2a;
    color: white;
}

.btn-primary:hover {
    background: #1a5621;
    transform: translateY(-1px);
}
.btn-success {
    background: none;
    font-size: 1.5rem;
    color: green;
}

.btn-danger:hover {
    transform: translateY(-1px);
}
.btn-danger {
    font-size: 1.5rem;
    color: red;
}

.btn-danger:hover {
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.9;
    cursor: not-allowed;
    transform: none;
}

/* Checkbox Styles */
input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    border: 2px solid #dee2e6;
    border-radius: 4px;
}

/* Actions Column */
.actions {
    display: flex;
    gap: 0.5rem;
    justify-content: start; /* Center the buttons horizontally */
    align-items: start;      /* Align buttons vertically */
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .tab-navigation {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .manage-table {
        display: block;
        overflow-x: auto;
    }
    
    .btn-sm {
        padding: 0.4rem 0.6rem;
    }
    
    .actions {
        gap: 0.5rem;
    }
}
#viewModal > div {
    background: white;
    border-radius: 8px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    max-width: 1200px;
    width: 95%;
    position: relative;
    margin: 20px auto;
}

#modalBody {
    margin-top: 0;
    background: transparent;
}
 .delete-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .delete-modal.active {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .delete-modal-content {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        width: 90%;
        max-width: 400px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        animation: slideUp 0.3s ease;
    }

    .delete-modal-icon {
        color: #dc2626;
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .delete-modal-title {
        color: #1f2937;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .delete-modal-message {
        color: #6b7280;
        margin-bottom: 2rem;
    }

    .delete-modal-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .btn-delete-confirm {
        background: #dc2626;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-delete-confirm:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .btn-delete-cancel {
        background: #e5e7eb;
        color: #4b5563;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-delete-cancel:hover {
        background: #d1d5db;
        transform: translateY(-1px);
    }

    /* Toast Notification */
    .toast {
        position: fixed;
        top: 180px;
        right: 1rem;
        background: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 1000;
        animation: slideInRight 0.3s ease, fadeOut 0.3s ease 3s forwards;
    }

    .toast-success {
        border-left: 4px solid #059669;
    }

    .toast-icon {
        color: #059669;
        font-size: 1.5rem;
    }

    .toast-message {
        color: #1f2937;
        font-weight: 500;
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeOut {
        to { opacity: 0; }
    }
</style>
<script>
    const activeTab = '<?php echo $activeTab; ?>';
    
    // DOM Elements
    const selectAll = document.getElementById('selectAll');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkAddBtn = document.getElementById('bulkAddBtn');
    const viewModal = document.getElementById('viewModal');
    const modalBody = document.getElementById('modalBody');
    const deleteModal = document.getElementById('deleteModal');

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        initializeCheckboxes();
        initializeButtons();
        initializeViewButtons();
        handleAlertMessage();
    });

    // Checkbox Management
    function initializeCheckboxes() {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('item-checkbox');
            Array.from(checkboxes).forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            bulkDeleteBtn.disabled = !this.checked;
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-checkbox')) {
                const checkboxes = document.getElementsByClassName('item-checkbox');
                const checkedBoxes = Array.from(checkboxes).filter(cb => cb.checked);
                bulkDeleteBtn.disabled = checkedBoxes.length === 0;
            }
        });
    }

    // Button Initialization
    function initializeButtons() {
        // Bulk Add Button
        bulkAddBtn.addEventListener('click', function() {
            if (activeTab === 'news') {
                window.open('news.php', '_blank');
            } else if (activeTab === 'sliders') {
                window.open('slider.php', '_blank');
            } else if (activeTab === 'publication') {
                window.open('publication.php', '_blank');
            }
        });

        // Bulk Delete Button
        bulkDeleteBtn.addEventListener('click', handleBulkDelete);
    }

    // Delete Functions
    let deleteItemId = null;
    let deleteItemType = null;

    function deleteItem(id, type) {
        deleteItemId = id;
        deleteItemType = type;
        deleteModal.classList.add('active');
    }

    function cancelDelete() {
        deleteModal.classList.remove('active');
        deleteItemId = null;
        deleteItemType = null;
    }

    function confirmDelete() {
        deleteModal.classList.remove('active');
        
        fetch(`api/delete_${deleteItemType}.php?id=${deleteItemId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Item deleted successfully!');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Error deleting item: ' + (data.error || 'Unknown error'), false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting item: ' + error.message, false);
        });
    }

    function handleBulkDelete() {
        const checkedBoxes = Array.from(document.getElementsByClassName('item-checkbox')).filter(cb => cb.checked);
        const ids = checkedBoxes.map(cb => cb.value);
        
        if (ids.length > 0) {
            deleteModal.classList.add('active');
            document.querySelector('.delete-modal-message').textContent = 
                `Are you sure you want to delete ${ids.length} items? This action cannot be undone.`;
            
            document.querySelector('.btn-delete-confirm').onclick = function() {
                deleteModal.classList.remove('active');
                
                Promise.all(ids.map(id => 
                    fetch(`api/delete_${activeTab}.php?id=${id}`, {
                        method: 'POST'
                    }).then(response => response.json())
                ))
                .then(results => {
                    const success = results.every(r => r.success);
                    if (success) {
                        showToast(`Successfully deleted ${ids.length} items`);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error deleting some items', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error deleting items: ' + error.message, false);
                });
            };
        }
    }

    // View Functions
function initializeViewButtons() {
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            
            // Show the modal properly
            const viewModal = document.getElementById('viewModal');
            viewModal.style.display = 'flex'; // Set display to flex first
            viewModal.style.opacity = '1'; // Then set opacity
            viewModal.classList.add('active');
            
            // Prevent background scrolling
            document.body.style.overflow = 'hidden';
            
            showLoadingState();

            if (type === 'sliders') {
                handleSliderView(id);
            } else if (type === 'publication') {
                handlePublicationView(id);
            }
        });
    });
}

    function showLoadingState() {
        modalBody.innerHTML = `
            <div style="padding:2rem;text-align:center;">
                <i class="fas fa-spinner fa-spin"></i> Loading...
            </div>
        `;
    }

    function handleSliderView(id) {
        fetch(`api/get_slider_file.php?id=${id}`)
            .then(response => {
                const contentType = response.headers.get('Content-Type');
                if (contentType?.startsWith('image/')) {
                    return response.blob().then(blob => {
                        const url = URL.createObjectURL(blob);
                        modalBody.innerHTML = `<img src="${url}" alt="Slider Image" style="max-width:100%;border-radius:8px;">`;
                    });
                } else if (contentType?.startsWith('video/')) {
                    return response.blob().then(blob => {
                        const url = URL.createObjectURL(blob);
                        modalBody.innerHTML = `<video src="${url}" controls style="max-width:100%;border-radius:8px;"></video>`;
                    });
                } else {
                    modalBody.innerHTML = '<div style="color:red;">Unsupported file type.</div>';
                }
            })
            .catch(() => {
                modalBody.innerHTML = '<div style="color:red;">Error loading file.</div>';
            });
    }

    function handlePublicationView(id) {
        fetch(`./api/get_publication_images.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.images?.length > 0) {
                    modalBody.innerHTML = `
                        <h2 style="margin-bottom:1rem;color:#216c2a;">${data.title}</h2>
                        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;justify-content:center;background:white;padding:2rem;border-radius:8px;">
                            ${data.images.map(image => `
                                <div style="width:300px;">
                                    <img src="${image.file_path}" 
                                         alt="Publication Image" 
                                         style="width:100%;height:auto;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                                    <div style="margin-top:0.5rem;text-align:left;">
                                        <p style="font-size:0.9rem;color:#216c2a;margin:0;">
                                            <strong>Page ${image.position + 1}</strong>
                                        </p>
                                        <p style="font-size:0.8rem;color:#666;margin:0.2rem 0;word-break:break-word;">
                                            ${image.original_filename}
                                        </p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = '<div style="color:red;padding:2rem;">No images found for this publication.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalBody.innerHTML = '<div style="color:red;padding:2rem;">Error loading publication images.</div>';
            });
    }

// Replace the existing utility functions with this code:

// Utility Functions
function showToast(message, success = true) {
    // Remove any existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast ${success ? 'toast-success' : 'toast-error'}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${success ? 'fa-check-circle' : 'fa-times-circle'}"></i>
        </div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    document.body.appendChild(toast);

    // Fade out and remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.5s ease forwards';
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

function handleAlertMessage() {
    const alertMessage = document.getElementById('alertMessage');
    if (alertMessage) {
        // Show the alert
        alertMessage.style.opacity = '1';
        alertMessage.style.transform = 'translateY(0)';

        // Hide after 5 seconds
        setTimeout(() => {
            alertMessage.style.opacity = '0';
            alertMessage.style.transform = 'translateY(-20px)';
            setTimeout(() => alertMessage.remove(), 200);
        }, 2000);
    }
}
function closeModal(modalId = 'viewModal') {
    const modal = document.getElementById(modalId);
    if (modal) {
        // First fade out
        modal.style.opacity = '0';
        
        // After fade animation completes
        setTimeout(() => {
            if (modalId === 'successModal') {
                modal.remove(); // Remove success modal completely
            } else {
                modal.classList.remove('active');
                modal.style.display = 'none'; // Hide the modal
                modal.style.opacity = ''; // Reset opacity
                modal.style.animation = ''; // Reset animation
                
                // Clear modal content if it's the view modal
                if (modalId === 'viewModal') {
                    document.getElementById('modalBody').innerHTML = '';
                }
            }
            
            // Re-enable page interaction
            document.body.style.overflow = '';
            modal.style.pointerEvents = '';
        }, 300);
    }
}
// Add auto-hide for success modal
document.addEventListener('DOMContentLoaded', function() {
    const successModal = document.getElementById('successModal');
    if (successModal) {
        setTimeout(() => {
            closeModal('successModal');
        }, 2000);
    }
});
</script>
