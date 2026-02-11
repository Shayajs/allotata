// Scripts pour les blocs de cours - Version publique

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion vidéo épinglée et audio mobile
        const videoBlocks = document.querySelectorAll('[data-video-block-id]');
        
        videoBlocks.forEach(block => {
            const video = block.querySelector('video');
            const iframe = block.querySelector('iframe');
            const isPinned = block.classList.contains('video-pinned-container');
            
            if (!video && !iframe) return;
            
            // Pour les vidéos uploadées : gestion audio mobile
            if (video && window.innerWidth < 768) {
                const audioControl = block.querySelector('.video-audio-control');
                const playPauseBtn = block.querySelector('.video-play-pause');
                const playIcon = block.querySelector('.play-icon');
                const pauseIcon = block.querySelector('.pause-icon');
                
                if (audioControl && playPauseBtn && playIcon && pauseIcon) {
                    let isIntersecting = true;
                    
                    // Observer pour détecter quand la vidéo sort de l'écran
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            isIntersecting = entry.isIntersecting;
                            if (!isIntersecting && !video.paused) {
                                // Vidéo hors écran et en lecture : afficher contrôle audio
                                audioControl.classList.add('show');
                                audioControl.classList.remove('hidden');
                            } else {
                                // Vidéo visible : masquer contrôle audio
                                audioControl.classList.remove('show');
                                audioControl.classList.add('hidden');
                            }
                        });
                    }, { threshold: 0.1 });
                    
                    observer.observe(video);
                    
                    // Gérer play/pause depuis le bouton
                    playPauseBtn.addEventListener('click', () => {
                        if (video.paused) {
                            video.play();
                            playIcon.classList.add('hidden');
                            pauseIcon.classList.remove('hidden');
                        } else {
                            video.pause();
                            playIcon.classList.remove('hidden');
                            pauseIcon.classList.add('hidden');
                        }
                    });
                    
                    // Mettre à jour l'icône selon l'état
                    video.addEventListener('play', () => {
                        if (!isIntersecting) {
                            playIcon.classList.add('hidden');
                            pauseIcon.classList.remove('hidden');
                        }
                    });
                    
                    video.addEventListener('pause', () => {
                        if (!isIntersecting) {
                            playIcon.classList.remove('hidden');
                            pauseIcon.classList.add('hidden');
                        }
                    });
                }
            }
        });
    });
})();
