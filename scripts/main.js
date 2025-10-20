 function showToast(type, message) {
            let toastId = type === 'success' ? 'success_toast' : 'error_toast';
            let messageId = type === 'success' ? 'success_message' : 'error_message';

            let toast = document.getElementById(toastId);
            let msg = document.getElementById(messageId);

            msg.textContent = message;
            toast.style.display = 'block';
            toast.style.opacity = 0;

            anime({
                targets: `#${toastId}`,
                opacity: [0, 1],
                translateY: [-20, 0],
                duration: 600,
                easing: 'easeOutQuad'
            });

            // Hide after 3 seconds
            setTimeout(() => {
                anime({
                    targets: `#${toastId}`,
                    opacity: [1, 0],
                    translateY: [0, -20],
                    duration: 600,
                    easing: 'easeInQuad',
                    complete: () => {
                        toast.style.display = 'none';
                    }
                });
            }, 3000);
        }
