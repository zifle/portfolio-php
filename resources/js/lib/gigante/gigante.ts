import "./gigante.css";

let viewContainer: HTMLElement;
export function toggleGigante(ev: MouseEvent) {
    const i = ev.target;

    if (i instanceof HTMLImageElement) {
        const rect = i.getClientRects().item(0);
        const viewContTainer = document.getElementById(
            'view-container-container',
        );
        const vc = document.getElementById('view-container');

        if (vc && viewContTainer && rect) {
            viewContainer = vc;
            viewContainer.addEventListener('click', () => closeGigante(i), {
                once: true,
            });
            const iClone = i.cloneNode() as HTMLImageElement;
            iClone.setAttribute('sizes', '100vw'); // Make sure the browser loads the proper image
            iClone.classList.remove('w-100');
            viewContainer.appendChild(iClone);
            viewContTainer.classList.remove('hidden');
            viewContainer.style.top = rect.top + 'px';
            viewContainer.style.left = rect.left + 'px';
            viewContainer.style.width = rect.width + 'px';
            viewContainer.style.height = rect.height + 'px';

            requestAnimationFrame(() => {
                viewContTainer.classList.add('blur-bg');
                viewContainer.style.top = '0';
                viewContainer.style.left = '0';
                viewContainer.style.width = '100vw';
                viewContainer.style.height = '100vh';
            });
        }
    }
}

function closeGigante(i: HTMLImageElement) {
    const rect = i.getClientRects().item(0);

    if (viewContainer && rect) {
        viewContainer.style.top = rect.top + 'px';
        viewContainer.style.left = rect.left + 'px';
        viewContainer.style.width = rect.width + 'px';
        viewContainer.style.height = rect.height + 'px';
        viewContainer.parentElement?.classList.remove('blur-bg');
        setTimeout(() => {
            const child = viewContainer.firstChild;

            if (child) {
                viewContainer.removeChild(child);
            }

            viewContainer.parentElement?.classList.add('hidden');
        }, 500);
    }
}
