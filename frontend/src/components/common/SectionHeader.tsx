type SectionHeaderProps = {
  eyebrow: string;
  title: string;
  action?: React.ReactNode;
};

export function SectionHeader({ eyebrow, title, action }: SectionHeaderProps) {
  return (
    <div className="mb-8 flex items-end justify-between gap-4">
      <div>
        <p className="text-xs uppercase tracking-[0.25em] text-zinc-500">{eyebrow}</p>
        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-zinc-900">{title}</h2>
      </div>
      {action}
    </div>
  );
}
