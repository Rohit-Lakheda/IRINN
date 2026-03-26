@extends('user.layout')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h4 class="mb-0">{{ $message->subject }}</h4>
                    <a href="{{ route('user.messages.index') }}" class="btn btn-white btn-sm" style="border-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                        </svg>
                        Back to Inbox
                    </a>
                </div>
                <div class="card-body p-4 theme-forms">
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex flex-wrap gap-3">
                            <div>
                                <strong style="color: #2c3e50;">From:</strong> 
                                <span class="text-muted">{{ ucfirst($message->sent_by) }}</span>
                            </div>
                            <div>
                                <strong style="color: #2c3e50;">Date:</strong> 
                                <span class="text-muted">{{ $message->created_at->format('F d, Y h:i A') }}</span>
                            </div>
                            @if($message->read_at)
                            <div>
                                <strong style="color: #2c3e50;">Read:</strong> 
                                <span class="text-muted">{{ $message->read_at->format('F d, Y h:i A') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="message-content mb-4 p-3 form-control" style="background-color: #f8f9fa; border-radius: 10px; min-height: 100px;">
                        {!! nl2br(e($message->message)) !!}
                    </div>

                    @if($message->user_reply)
                        <div class="alert alert-info border-c-blue mb-4" style="border-radius: 12px; background-color: #e8f4f8;">
                            <div class="d-flex align-items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#3498db" class="me-2 mt-1" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                                </svg>
                                <div class="flex-grow-1">
                                    <strong style="color: #2c3e50;">Your Reply:</strong>
                                    <p class="mb-2 mt-2" style="color: #555;">{!! nl2br(e($message->user_reply)) !!}</p>
                                    <small class="text-muted">Replied on: {{ $message->user_replied_at->format('F d, Y h:i A') }}</small>
                                </div>
                            </div>
                        </div>
                    @elseif($message->sent_by === 'admin')
                        <div class="card border-c-blue shadow-sm mt-4" style="border-radius: 12px; background-color: #fff9e6; border-left: 4px solid #f39c12;">
                            <div class="card-body p-4">
                                <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">Reply to Admin</h5>
                                <form method="POST" action="{{ route('user.messages.reply', $message->id) }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="user_reply" class="form-label" style="color: #2c3e50; font-weight: 500;">Your Reply</label>
                                        <textarea 
                                            class="form-control @error('user_reply') is-invalid @enderror" 
                                            id="user_reply" 
                                            name="user_reply" 
                                            rows="5" 
                                            placeholder="Type your reply here..." 
                                            required
                                            style="border-radius: 8px; border: 1px solid #e0e0e0;">{{ old('user_reply') }}</textarea>
                                        @error('user_reply')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">You can only reply once to each message.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 500;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                            <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm-1.138-1.138L13.713 1.48l-4.338-2.761L5.498 8.932Z"/>
                                        </svg>
                                        Send Reply
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
