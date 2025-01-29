import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css'; // Ensure CSS is imported

/**
 * Configures and returns a SweetAlert2 instance with default options.
 * @returns {Object} The configured SweetAlert2 instance.
 */
export function useSweetAlert() {
    // Configure global defaults for SweetAlert2
    const swal = Swal.mixin({
        confirmButtonColor: '#3085d6', // Blue confirm button
        cancelButtonColor: '#d33', // Red cancel button
        showCancelButton: true, // Show cancel button by default
        showCloseButton: true, // Show close button
        allowOutsideClick: false, // Prevent closing by clicking outside
        allowEscapeKey: false, // Prevent closing by pressing Esc
    });

    return swal;
}