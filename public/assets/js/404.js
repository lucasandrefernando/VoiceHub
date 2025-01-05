document.addEventListener('DOMContentLoaded', function () {
    const errorCode = document.querySelector('.error-code');
    const message = "root@voicehub:~$ ls /pagina-solicitada\nls: cannot access '/pagina-solicitada': No such file or directory\nroot@voicehub:~$ _";
    const typingMessage = document.getElementById('typingMessage');
    let i = 0;

    errorCode.addEventListener('mouseover', function () {
        this.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
    });

    errorCode.addEventListener('animationend', function () {
        this.style.animation = '';
    });

    function typeWriter() {
        if (i < message.length) {
            typingMessage.innerHTML += message.charAt(i);
            i++;
            setTimeout(typeWriter, 30);
        }
    }

    typeWriter();

    setInterval(() => {
        if (i >= message.length) {
            typingMessage.innerHTML = message.slice(0, -1) + (typingMessage.innerHTML.endsWith('_') ? ' ' : '_');
        }
    }, 500);
});

document.head.insertAdjacentHTML('beforeend', `
    <style>
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
`);