import Image from 'next/image';
import Link from 'next/link';

interface PostCardProps {
  slug: string;
  title: string;
  excerpt: string;
  coverImage: string;
  date: string;
  author?: string;
}

export default function PostCard({
  slug,
  title,
  excerpt,
  coverImage,
  date,
  author,
}: PostCardProps) {
  return (
    <Link href={`/blog/${slug}`}>
      <article className="group grid overflow-hidden rounded-xl bg-white shadow-sm transition-shadow hover:shadow-md">
        <div className="relative h-48 w-full overflow-hidden">
          <Image
            src={coverImage}
            alt={title}
            fill
            className="object-cover transition-transform duration-300 group-hover:scale-105"
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
          />
        </div>
        <div className="grid gap-3 p-6">
          <h2 className="text-xl font-semibold text-slate-900 transition-colors group-hover:text-slate-700">
            {title}
          </h2>
          <p className="text-sm text-slate-600 line-clamp-3">
            {excerpt}
          </p>
          <div className="flex items-center justify-between text-xs text-slate-500">
            <time dateTime={date}>
              {new Date(date).toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </time>
            {author && <span>{author}</span>}
          </div>
        </div>
      </article>
    </Link>
  );
}
