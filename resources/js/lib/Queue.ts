export default class Queue<T> {
    constructor(
        private items: T[] = [],
        private open: boolean = true,
    ) {}

    isOpen() {
        return this.open;
    }

    close() {
        this.open = false;
    }

    isEmpty() {
        return this.items.length === 0;
    }

    enqueue(item: T) {
        this.items.push(item);
    }

    async dequeue(): Promise<T | undefined> {
        if (this.isOpen() && this.isEmpty()) {
            await new Promise<void>((res) => {
                const check = () => {
                    if (this.isOpen() && this.isEmpty()) {
                        setTimeout(check, 50);
                    } else {
                        res();
                    }
                };
                setTimeout(check, 50);
            });
        }

        return this.isEmpty() ? undefined : this.items.shift();
    }
}
