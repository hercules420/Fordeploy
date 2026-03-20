<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    public function farmOwnerNotifications()
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner) {
            return redirect()->route('farmowner.dashboard')->with('error', 'Farm owner profile not found.');
        }

        $notifications = Notification::forUser(Auth::id())
            ->where(function ($query) {
                $query->where('type', 'alert')
                    ->orWhere('title', 'like', 'Customer Complaint:%');
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('farmowner.notifications.index', compact('notifications'));
    }

    public function farmOwnerMarkNotificationsRead()
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner) {
            return redirect()->route('farmowner.dashboard')->with('error', 'Farm owner profile not found.');
        }

        Notification::forUser(Auth::id())
            ->where(function ($query) {
                $query->where('type', 'alert')
                    ->orWhere('title', 'like', 'Customer Complaint:%');
            })
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->route('farmowner.notifications.index')->with('success', 'All complaint notifications marked as read.');
    }

    public function farmOwnerIndex()
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner) {
            return redirect()->route('farmowner.dashboard')->with('error', 'Farm owner profile not found.');
        }

        $tickets = $farmOwner->supportTickets()
            ->with(['latestMessage.sender:id,name'])
            ->latest('last_message_at')
            ->latest('updated_at')
            ->paginate(12);

        return view('farmowner.support.index', compact('tickets'));
    }

    public function farmOwnerStore(Request $request)
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner) {
            return redirect()->route('farmowner.dashboard')->with('error', 'Farm owner profile not found.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $ticket = SupportTicket::create([
            'farm_owner_id' => $farmOwner->id,
            'subject' => $validated['subject'],
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'farm_owner',
            'message' => $validated['message'],
        ]);

        return redirect()->route('farmowner.support.show', $ticket)->with('success', 'Support ticket created.');
    }

    public function farmOwnerShow(SupportTicket $ticket)
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner || $ticket->farm_owner_id !== $farmOwner->id) {
            abort(403, 'Unauthorized action.');
        }

        $ticket->load([
            'farmOwner.user:id,name,email',
            'messages.sender:id,name,role',
        ]);

        return view('farmowner.support.show', compact('ticket'));
    }

    public function farmOwnerReply(Request $request, SupportTicket $ticket)
    {
        $farmOwner = Auth::user()->farmOwner;

        if (!$farmOwner || $ticket->farm_owner_id !== $farmOwner->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticket->status === 'closed') {
            return redirect()->back()->with('error', 'This ticket is already closed.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'farm_owner',
            'message' => $validated['message'],
        ]);

        $ticket->update(['last_message_at' => now()]);

        return redirect()->back()->with('success', 'Message sent to super admin.');
    }

    public function adminIndex()
    {
        $tickets = SupportTicket::with([
            'farmOwner.user:id,name,email',
            'latestMessage.sender:id,name,role',
        ])
            ->latest('last_message_at')
            ->latest('updated_at')
            ->paginate(20);

        return view('superadmin.support.index', compact('tickets'));
    }

    public function adminShow(SupportTicket $ticket)
    {
        $ticket->load([
            'farmOwner.user:id,name,email',
            'messages.sender:id,name,role',
        ]);

        return view('superadmin.support.show', compact('ticket'));
    }

    public function adminReply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->status === 'closed') {
            return redirect()->back()->with('error', 'This ticket is already closed.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'superadmin',
            'message' => $validated['message'],
        ]);

        $ticket->update(['last_message_at' => now()]);

        return redirect()->back()->with('success', 'Reply sent to farm owner.');
    }

    public function adminClose(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'closed']);

        return redirect()->back()->with('success', 'Ticket closed successfully.');
    }
}
