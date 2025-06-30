import React, { useState, FormEvent } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Loader2, Send, Settings } from 'lucide-react';
import ReplyRefinementControls, { RefinementOptions } from '@/components/ui/reply-refinement-controls';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

interface Email {
  id: string;
  subject: string;
  from: string;
  to: string;
  date: string;
  body: string;
  html?: string;
  message_id: string;
}

interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
}

interface ShowProps extends PageProps {
  email: Email;
  latestReply?: string;
  chatHistory?: ChatMessage[];
  signature?: string;
  message?: string;
  success?: boolean;
}

export default function Show({ email, latestReply, chatHistory = [], signature = '', message, success }: ShowProps) {
  const [isGenerating, setIsGenerating] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [showChatHistory, setShowChatHistory] = useState(false);
  const [useAdvancedControls, setUseAdvancedControls] = useState(false);
  const [refinementOptions, setRefinementOptions] = useState<RefinementOptions>({
    tone: 'professional',
    length: 'medium',
    formality: 3,
    urgency: 'normal',
  });

  const { data: generateData, setData: setGenerateData, post: generatePost } = useForm({
    instruction: '',
    refinementOptions: null as RefinementOptions | null,
  });

  const { data: replyData, setData: setReplyData, post: replyPost } = useForm({
    reply: latestReply || '',
    signature: signature || '',
  });

  const handleGenerateReply = (e?: FormEvent) => {
    if (e) e.preventDefault();
    setIsGenerating(true);
    
    // Prepare the data based on which method is being used
    const requestData = useAdvancedControls 
      ? { refinementOptions } 
      : { instruction: generateData.instruction };
    
    generatePost(`/inbox/${email.id}/generate-reply`, {
      data: requestData,
      preserveScroll: true,
      onSuccess: () => {
        setIsGenerating(false);
        if (!useAdvancedControls) {
          setGenerateData('instruction', '');
        }
      },
      onError: () => {
        setIsGenerating(false);
      },
    });
  };

  const handleRefinementRefine = () => {
    handleGenerateReply();
  };

  const handleSendReply = (e: FormEvent) => {
    e.preventDefault();
    setIsSending(true);
    
    replyPost(`/inbox/${email.id}/send-reply`, {
      preserveScroll: true,
      onSuccess: () => {
        setIsSending(false);
      },
      onError: () => {
        setIsSending(false);
      },
    });
  };

  // Update reply form data when latestReply changes
  React.useEffect(() => {
    if (latestReply) {
      setReplyData('reply', latestReply);
    }
  }, [latestReply]);

  return (
    <>
      <Head title={email.subject} />
      
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          {message && (
            <div className={`mb-4 p-4 rounded-md ${success !== false ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}`}>
              {message}
            </div>
          )}

          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div className="p-6 text-gray-900 dark:text-gray-100">
              <div className="flex justify-between items-start mb-6">
                <h1 className="text-2xl font-bold">{email.subject}</h1>
                <Button variant="outline" asChild>
                  <Link
                    href="/inbox"
                    prefetch
                  >
                    Back to Inbox
                  </Link>
                </Button>
              </div>
              
              <div className="mb-6">
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  <p><strong>From:</strong> {email.from}</p>
                  <p><strong>To:</strong> {email.to}</p>
                  <p><strong>Date:</strong> {formatDate(email.date)}</p>
                </div>
              </div>
              
              <div className="border-t border-gray-200 dark:border-gray-700 pt-4 mb-8">
                <div className="prose dark:prose-invert max-w-none">
                  {/* If HTML content is available, render it, otherwise use plain text */}
                  {email.html ? (
                    <div dangerouslySetInnerHTML={{ __html: email.html }} />
                  ) : (
                    <pre className="whitespace-pre-wrap font-sans">{email.body}</pre>
                  )}
                </div>
              </div>
            </div>
          </div>
          
          {/* AI Prompt Form */}
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div className="p-6 text-gray-900 dark:text-gray-100">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-bold">Generate AI Reply</h2>
                <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    onClick={() => setUseAdvancedControls(!useAdvancedControls)}
                    variant="outline"
                    size="sm"
                  >
                    <Settings className="mr-2 h-4 w-4" />
                    {useAdvancedControls ? 'Simple Mode' : 'Advanced Controls'}
                  </Button>
                  {chatHistory.length > 0 && (
                    <Button
                      type="button"
                      onClick={() => setShowChatHistory(!showChatHistory)}
                      variant="ghost"
                      size="sm"
                    >
                      {showChatHistory ? 'Hide Chat History' : 'Show Chat History'}
                    </Button>
                  )}
                </div>
              </div>
              
              {useAdvancedControls ? (
                <ReplyRefinementControls
                  options={refinementOptions}
                  onChange={setRefinementOptions}
                  onRefine={handleRefinementRefine}
                  isRefining={isGenerating}
                  showQuickActions={true}
                />
              ) : (
                <form onSubmit={handleGenerateReply}>
                  <div className="mb-4">
                    <label htmlFor="instruction" className="block text-sm font-medium mb-1">
                      Instructions for AI
                    </label>
                    <textarea
                      id="instruction"
                      value={generateData.instruction}
                      onChange={(e) => setGenerateData('instruction', e.target.value)}
                      rows={3}
                      placeholder="e.g., 'Reply in a friendly tone' or 'Make it shorter'"
                      className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800 focus:ring-opacity-50"
                    />
                  </div>
                  
                  <Button
                    type="submit"
                    disabled={isGenerating}
                    variant="default"
                  >
                    {isGenerating && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                    {isGenerating ? 'Generating...' : 'Generate AI Reply'}
                  </Button>
                </form>
              )}
              
              {/* Chat History */}
              {showChatHistory && chatHistory.length > 0 && (
                <div className="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                  <h3 className="text-lg font-medium mb-3">Chat History</h3>
                  <div className="space-y-4">
                    {chatHistory.map((message, index) => (
                      <div 
                        key={index} 
                        className={`p-3 rounded-lg ${
                          message.role === 'user' 
                            ? 'bg-gray-100 dark:bg-gray-700 ml-8' 
                            : 'bg-indigo-50 dark:bg-indigo-900 mr-8'
                        }`}
                      >
                        <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">
                          {message.role === 'user' ? 'You' : 'AI Assistant'}
                        </div>
                        <div className="whitespace-pre-wrap">{message.content}</div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
          
          {/* Reply Form */}
          {latestReply && (
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
              <div className="p-6 text-gray-900 dark:text-gray-100">
                <h2 className="text-xl font-bold mb-4">Edit & Send Reply</h2>
                
                <form onSubmit={handleSendReply}>
                  <div className="mb-4">
                    <textarea
                      id="reply"
                      value={replyData.reply}
                      onChange={(e) => setReplyData('reply', e.target.value)}
                      rows={10}
                      className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800 focus:ring-opacity-50"
                      required
                    />
                  </div>
                  
                  {/* Signature textarea */}
                  <div className="mb-4">
                    <label htmlFor="signature" className="block text-sm font-medium mb-1">Signature</label>
                    <textarea
                      id="signature"
                      value={replyData.signature}
                      onChange={(e) => setReplyData('signature', e.target.value)}
                      rows={6}
                      className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800 focus:ring-opacity-50"
                    />
                  </div>
                  <div>
                    <Button
                      type="submit"
                      disabled={isSending}
                      variant="default"
                    >
                      {isSending ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          Sending...
                        </>
                      ) : (
                        <>
                          Send
                          <Send className="ml-2 h-4 w-4" />
                        </>
                      )}
                    </Button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}

Show.layout = (page: React.ReactNode) => <AppLayout children={page} />;
