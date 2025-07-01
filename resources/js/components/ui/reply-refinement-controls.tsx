import React from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Wand2 } from 'lucide-react';

export interface RefinementOptions {
  tone: 'professional' | 'friendly' | 'casual' | 'formal' | 'warm' | 'direct';
  length: 'concise' | 'medium' | 'detailed';
  formality: number; // 1-5 scale
  urgency: 'low' | 'normal' | 'high';
  customInstruction?: string;
}

interface ReplyRefinementControlsProps {
  options: RefinementOptions;
  onChange: (options: RefinementOptions) => void;
  onRefine: () => void;
  isRefining?: boolean;
  showQuickActions?: boolean;
}

const toneOptions = [
  { value: 'professional', label: 'Professional', description: 'Business-appropriate and polished' },
  { value: 'friendly', label: 'Friendly', description: 'Warm and approachable' },
  { value: 'casual', label: 'Casual', description: 'Relaxed and informal' },
  { value: 'formal', label: 'Formal', description: 'Traditional and respectful' },
  { value: 'warm', label: 'Warm', description: 'Caring and personal' },
  { value: 'direct', label: 'Direct', description: 'Clear and to-the-point' },
];

const lengthOptions = [
  { value: 'concise', label: 'Concise', description: 'Short and brief' },
  { value: 'medium', label: 'Medium', description: 'Balanced length' },
  { value: 'detailed', label: 'Detailed', description: 'Comprehensive and thorough' },
];

const urgencyOptions = [
  { value: 'low', label: 'Low Priority', description: 'Can wait, no rush' },
  { value: 'normal', label: 'Normal', description: 'Standard response time' },
  { value: 'high', label: 'High Priority', description: 'Urgent, needs quick response' },
];

const quickActions = [
  { action: 'Make it shorter', instruction: 'Make this reply more concise and brief while keeping the main points.' },
  { action: 'More professional', instruction: 'Make this reply more professional and business-appropriate.' },
  { action: 'More friendly', instruction: 'Make this reply warmer and more friendly in tone.' },
  { action: 'Add details', instruction: 'Add more details and explanation to make this reply more comprehensive.' },
  { action: 'More direct', instruction: 'Make this reply more direct and to-the-point.' },
  { action: 'Fix grammar', instruction: 'Fix any grammar, spelling, or writing issues in this reply.' },
];

export default function ReplyRefinementControls({
  options,
  onChange,
  onRefine,
  isRefining = false,
  showQuickActions = true,
}: ReplyRefinementControlsProps) {
  const handleQuickAction = (instruction: string) => {
    onChange({ ...options, customInstruction: instruction });
  };

  const getFormalityLabel = (value: number) => {
    const labels = ['Very Casual', 'Casual', 'Neutral', 'Formal', 'Very Formal'];
    return labels[value - 1] || 'Neutral';
  };

  const generateInstruction = () => {
    const parts = [];
    
    // Tone instruction
    const toneOption = toneOptions.find(t => t.value === options.tone);
    if (toneOption) {
      parts.push(`Write in a ${toneOption.label.toLowerCase()} tone`);
    }
    
    // Length instruction
    const lengthOption = lengthOptions.find(l => l.value === options.length);
    if (lengthOption) {
      parts.push(`make it ${lengthOption.label.toLowerCase()}`);
    }
    
    // Formality instruction
    if (options.formality !== 3) {
      parts.push(`with ${getFormalityLabel(options.formality).toLowerCase()} language`);
    }
    
    // Urgency instruction
    if (options.urgency !== 'normal') {
      const urgencyOption = urgencyOptions.find(u => u.value === options.urgency);
      if (urgencyOption && options.urgency === 'high') {
        parts.push('and convey appropriate urgency');
      } else if (urgencyOption && options.urgency === 'low') {
        parts.push('and indicate this is low priority');
      }
    }
    
    // Custom instruction takes precedence
    if (options.customInstruction?.trim()) {
      return options.customInstruction;
    }
    
    return parts.length > 0 ? parts.join(', ') + '.' : 'Refine this reply to make it better.';
  };

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Tone Selection */}
        <div className="space-y-2">
          <Label htmlFor="tone">Tone</Label>
          <Select
            value={options.tone}
            onValueChange={(value) => onChange({ ...options, tone: value as RefinementOptions['tone'] })}
          >
            <SelectTrigger>
              <SelectValue placeholder="Select tone" />
            </SelectTrigger>
            <SelectContent>
              {toneOptions.map((tone) => (
                <SelectItem key={tone.value} value={tone.value}>
                  <div>
                    <div className="font-medium">{tone.label}</div>
                    <div className="text-xs text-muted-foreground">{tone.description}</div>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/* Length Selection */}
        <div className="space-y-2">
          <Label htmlFor="length">Length</Label>
          <Select
            value={options.length}
            onValueChange={(value) => onChange({ ...options, length: value as RefinementOptions['length'] })}
          >
            <SelectTrigger>
              <SelectValue placeholder="Select length" />
            </SelectTrigger>
            <SelectContent>
              {lengthOptions.map((length) => (
                <SelectItem key={length.value} value={length.value}>
                  <div>
                    <div className="font-medium">{length.label}</div>
                    <div className="text-xs text-muted-foreground">{length.description}</div>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Formality Slider */}
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <Label htmlFor="formality">Formality Level</Label>
          <Badge variant="secondary">{getFormalityLabel(options.formality)}</Badge>
        </div>
        <Slider
          id="formality"
          min={1}
          max={5}
          step={1}
          value={[options.formality]}
          onValueChange={(value) => onChange({ ...options, formality: value[0] })}
          className="w-full"
        />
        <div className="flex justify-between text-xs text-muted-foreground">
          <span>Very Casual</span>
          <span>Very Formal</span>
        </div>
      </div>

      {/* Urgency Selection */}
      <div className="space-y-2">
        <Label htmlFor="urgency">Urgency</Label>
        <Select
          value={options.urgency}
          onValueChange={(value) => onChange({ ...options, urgency: value as RefinementOptions['urgency'] })}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select urgency" />
          </SelectTrigger>
          <SelectContent>
            {urgencyOptions.map((urgency) => (
              <SelectItem key={urgency.value} value={urgency.value}>
                <div>
                  <div className="font-medium">{urgency.label}</div>
                  <div className="text-xs text-muted-foreground">{urgency.description}</div>
                </div>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Custom Instructions */}
      <div className="space-y-2">
        <Label htmlFor="customInstruction">Custom Instructions (Optional)</Label>
        <textarea
          id="customInstruction"
          value={options.customInstruction || ''}
          onChange={(e) => onChange({ ...options, customInstruction: e.target.value })}
          placeholder="Add any specific instructions for how to refine the reply..."
          rows={3}
          className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800 focus:ring-opacity-50"
        />
      </div>

      {/* Quick Actions */}
      {showQuickActions && (
        <div className="space-y-2">
          <Label>Quick Actions</Label>
          <div className="flex flex-wrap gap-2">
            {quickActions.map((action) => (
              <Button
                key={action.action}
                variant="outline"
                size="sm"
                onClick={() => handleQuickAction(action.instruction)}
              >
                {action.action}
              </Button>
            ))}
          </div>
        </div>
      )}

      {/* Generated Instruction Preview */}
      <div className="space-y-2">
        <Label>Generated Instruction</Label>
        <div className="p-3 bg-muted rounded-md text-sm">
          {generateInstruction()}
        </div>
      </div>

      {/* Refine Button */}
      <Button
        onClick={onRefine}
        disabled={isRefining}
        className="w-full"
        size="lg"
      >
        {isRefining ? (
          <>
            <Wand2 className="mr-2 h-4 w-4 animate-spin" />
            Refining Reply...
          </>
        ) : (
          <>
            <Wand2 className="mr-2 h-4 w-4" />
            Refine Reply
          </>
        )}
      </Button>
    </div>
  );
}