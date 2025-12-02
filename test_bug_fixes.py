#!/usr/bin/env python3
"""
Test script to verify bug fixes in review_local.py

Bug 1: Config.get() returning None should not cause AttributeError
Bug 2: get_changed_files() should respect commit_range parameter
"""

import sys
import tempfile
import os
from pathlib import Path

# Add current directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from review_local import Config, GitDiffCollector, ToolRunner


def test_bug1_none_config():
    """Test that tool methods handle None config gracefully."""
    print("Testing Bug 1: None config handling...")
    
    # Create a minimal config that returns None for tools
    with tempfile.NamedTemporaryFile(mode='w', suffix='.yaml', delete=False) as f:
        f.write("# Minimal config without tools section\n")
        f.write("output:\n")
        f.write("  file: test.json\n")
        config_path = f.name
    
    try:
        config = Config(config_path)
        tool_runner = ToolRunner(config)
        
        # These should not raise AttributeError
        result1, version1 = tool_runner.run_phpstan()
        print(f"  ✓ run_phpstan() returned: {result1[:30]}...")
        assert "disabled" in result1.lower() or "not" in result1.lower()
        
        result2, version2 = tool_runner.run_phpcs()
        print(f"  ✓ run_phpcs() returned: {result2[:30]}...")
        assert "disabled" in result2.lower() or "not" in result2.lower()
        
        result3, version3 = tool_runner.run_phpunit()
        print(f"  ✓ run_phpunit() returned: {result3[:30]}...")
        assert "disabled" in result3.lower() or "not" in result3.lower()
        
        print("✅ Bug 1 fix verified: No AttributeError when config returns None\n")
        return True
        
    except AttributeError as e:
        print(f"❌ Bug 1 NOT fixed: AttributeError raised: {e}\n")
        return False
    finally:
        os.unlink(config_path)


def test_bug2_commit_range():
    """Test that get_changed_files() uses commit_range parameter."""
    print("Testing Bug 2: commit_range parameter handling...")
    
    # Create a temporary git repository for testing
    with tempfile.TemporaryDirectory() as tmpdir:
        os.chdir(tmpdir)
        
        # Initialize git repo
        os.system('git init > /dev/null 2>&1')
        os.system('git config user.email "test@test.com"')
        os.system('git config user.name "Test User"')
        
        # Create initial commit
        Path('file1.txt').write_text('content1')
        os.system('git add file1.txt')
        os.system('git commit -m "Initial commit" > /dev/null 2>&1')
        
        # Create second commit
        Path('file2.txt').write_text('content2')
        os.system('git add file2.txt')
        os.system('git commit -m "Second commit" > /dev/null 2>&1')
        
        # Create third commit
        Path('file3.txt').write_text('content3')
        os.system('git add file3.txt')
        os.system('git commit -m "Third commit" > /dev/null 2>&1')
        
        collector = GitDiffCollector(target_branch='main')
        
        # Test with commit range - should only return file3.txt
        files_with_range = collector.get_changed_files('HEAD~1..HEAD')
        print(f"  Files with commit_range='HEAD~1..HEAD': {files_with_range}")
        
        # Test without commit range - should compare with main (all files)
        files_without_range = collector.get_changed_files()
        print(f"  Files without commit_range: {files_without_range}")
        
        # Verify the fix: commit_range should limit results
        if 'file3.txt' in files_with_range:
            print("  ✓ commit_range parameter is respected")
            print("✅ Bug 2 fix verified: get_changed_files() uses commit_range\n")
            return True
        else:
            print("  ❌ commit_range parameter not working correctly")
            print("❌ Bug 2 NOT fixed\n")
            return False


def test_bug2_consistency():
    """Test that get_diff() and get_changed_files() return consistent results."""
    print("Testing Bug 2: Consistency between get_diff() and get_changed_files()...")
    
    with tempfile.TemporaryDirectory() as tmpdir:
        os.chdir(tmpdir)
        
        # Initialize git repo
        os.system('git init > /dev/null 2>&1')
        os.system('git config user.email "test@test.com"')
        os.system('git config user.name "Test User"')
        
        # Create commits
        Path('file1.txt').write_text('content1')
        os.system('git add file1.txt')
        os.system('git commit -m "First" > /dev/null 2>&1')
        
        Path('file2.txt').write_text('content2')
        os.system('git add file2.txt')
        os.system('git commit -m "Second" > /dev/null 2>&1')
        
        collector = GitDiffCollector()
        
        # Test with same commit_range
        commit_range = 'HEAD~1..HEAD'
        diff = collector.get_diff(commit_range)
        files = collector.get_changed_files(commit_range)
        
        print(f"  Diff mentions file2.txt: {'file2.txt' in diff}")
        print(f"  Changed files includes file2.txt: {'file2.txt' in files}")
        
        # Both should reference file2.txt
        if ('file2.txt' in diff or 'b/file2.txt' in diff) and 'file2.txt' in files:
            print("  ✓ get_diff() and get_changed_files() are consistent")
            print("✅ Bug 2 consistency verified\n")
            return True
        else:
            print("  ❌ Inconsistency detected")
            print("❌ Bug 2 consistency NOT verified\n")
            return False


def main():
    """Run all tests."""
    print("=" * 70)
    print("Bug Fix Verification Tests")
    print("=" * 70)
    print()
    
    results = []
    
    # Test Bug 1
    try:
        results.append(("Bug 1: None config handling", test_bug1_none_config()))
    except Exception as e:
        print(f"❌ Bug 1 test failed with exception: {e}\n")
        results.append(("Bug 1: None config handling", False))
    
    # Test Bug 2
    try:
        results.append(("Bug 2: commit_range parameter", test_bug2_commit_range()))
    except Exception as e:
        print(f"❌ Bug 2 test failed with exception: {e}\n")
        results.append(("Bug 2: commit_range parameter", False))
    
    # Test Bug 2 consistency
    try:
        results.append(("Bug 2: Consistency check", test_bug2_consistency()))
    except Exception as e:
        print(f"❌ Bug 2 consistency test failed with exception: {e}\n")
        results.append(("Bug 2: Consistency check", False))
    
    # Summary
    print("=" * 70)
    print("Test Summary")
    print("=" * 70)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for test_name, result in results:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{status}: {test_name}")
    
    print()
    print(f"Results: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n🎉 All bug fixes verified successfully!")
        return 0
    else:
        print(f"\n⚠️  {total - passed} test(s) failed")
        return 1


if __name__ == '__main__':
    sys.exit(main())

