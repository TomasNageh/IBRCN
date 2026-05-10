<?php

/**
 * OBSERVER PATTERN — EventPublisher.php
 *
 * WHAT IS OBSERVER?
 * Like a YouTube channel subscription. The channel (EventPublisher) doesn't
 * know who its subscribers are individually. When it uploads a video (fires an
 * event), all subscribers (ReaderObserver) are automatically notified.
 *
 * WHY DO WE USE IT HERE?
 * When checkout completes or an account welcomes a reader, we publish one named event.
 * Observers translate events into emails/in-app notes without notification callers needing subscriber lists.
 *
 * FILE: EventPublisher.php
 * PURPOSE: Subject object storing subscribed observers and broadcasting typed notifications (`publish`).
 * USED BY: `NotificationService` after notable reader-owner milestones occur at checkout/welcome/order-ready edges.
 * DESIGN PATTERN: Observer subject (`ReaderObserver` implements watcher responsibilities).
 */

require_once __DIR__ . '/ReaderObserver.php';

// Keeps attached observers and notifies them whenever publish(...) fires domain happenings (observer subject).
class EventPublisher
{
    /** @var ReaderObserver[] */
    private array $readerObservers = array();

    /**
     * Registers another observer interested in reader-visible lifecycle announcements fired via publish().
     *
     * @param ReaderObserver $observer Observer instance that understands Reader-facing alerts/emails.
     * @return void
     */
    public function subscribe(ReaderObserver $observer): void
    {
        $this->readerObservers[] = $observer;
    }

    // Removes an observer from the list so it stops receiving notifications.
    // @param ReaderObserver $observer  The observer object to remove
    // @return void
    public function unsubscribe(ReaderObserver $observer): void
    {
        // Loop through the list and remove the matching observer by identity
        $this->readerObservers = array_filter(
            $this->readerObservers,
            fn($existingObserver) => $existingObserver !== $observer
        );

        // Re-index the array so there are no gaps in the keys
        $this->readerObservers = array_values($this->readerObservers);
    }

    /**
     * Fans out `$eventType` plus contextual `$payload` to every subscribed observer (typically ReaderObserver).
     *
     * @param string               $eventType Short symbolic channel ("checkout_success", etc.).
     * @param array<string, mixed> $payload Structured PHP payload mirrored across observers exactly once each publish call.
     * @return void
     */
    public function publish(string $eventType, array $payload): void
    {
        foreach ($this->readerObservers as $observer) {
            $observer->update($eventType, $payload);
        }
    }
}
